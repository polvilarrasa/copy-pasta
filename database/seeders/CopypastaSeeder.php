<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\CopypastaFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as SupportCollection;

class CopypastaSeeder extends Seeder
{
    private const TOTAL = 300;

    private const NSFW_RATIO = 0.1;

    private const MAX_VOTERS = 15;

    private const MAX_FAVORITES = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();
        $tags = Tag::query()->get();
        $favoriteFolders = $this->ensureFavoriteFolders($users);

        $nsfwCount = (int) round(self::TOTAL * self::NSFW_RATIO);

        for ($index = 0; $index < self::TOTAL; $index++) {
            $copypasta = Copypasta::factory()
                ->publishedDaysAgo(fake()->numberBetween(0, 90))
                ->when($index < $nsfwCount, fn (CopypastaFactory $factory) => $factory->nsfw())
                ->for($users->random(), 'user')
                ->create();

            $copypasta->tags()->attach(
                $tags->random(fake()->numberBetween(1, min(5, $tags->count())))->pluck('id'),
            );

            $this->seedVotesAndFavorites($copypasta, $users, $favoriteFolders);
        }
    }

    /**
     * Users created by factories skip the Registered event, so their default folder is created here.
     *
     * @param  Collection<int, User>  $users
     * @return SupportCollection<int, Folder>
     */
    private function ensureFavoriteFolders(Collection $users): SupportCollection
    {
        return $users->mapWithKeys(fn (User $user): array => [
            $user->getKey() => Folder::query()->firstOrCreate(
                ['user_id' => $user->getKey(), 'is_default' => true],
                ['name' => 'Favoritos', 'position' => 0],
            ),
        ]);
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  SupportCollection<int, Folder>  $favoriteFolders
     */
    private function seedVotesAndFavorites(Copypasta $copypasta, Collection $users, SupportCollection $favoriteFolders): void
    {
        $candidates = $users->reject(fn (User $user): bool => $user->is($copypasta->user));

        $voters = $candidates->random(fake()->numberBetween(0, min(self::MAX_VOTERS, $candidates->count())));

        $upvotes = 0;
        $downvotes = 0;

        foreach ($voters as $voter) {
            $value = fake()->boolean(75) ? 1 : -1;

            if ($value === 1) {
                $upvotes++;
            } else {
                $downvotes++;
            }

            $copypasta->votes()->create(['user_id' => $voter->getKey(), 'value' => $value]);
        }

        $favorites = $voters->random(fake()->numberBetween(0, min(self::MAX_FAVORITES, $voters->count())));

        foreach ($favorites as $user) {
            $favoriteFolders->get($user->getKey())?->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);
        }

        $copypasta->update([
            'upvotes_count' => $upvotes,
            'downvotes_count' => $downvotes,
            'score' => $upvotes - $downvotes,
            'favorites_count' => $favorites->count(),
        ]);
    }
}
