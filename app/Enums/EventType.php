<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case Copy = 'copy';
    case Share = 'share';
    case DetailView = 'detail_view';
    case VoteUp = 'vote_up';
    case VoteDown = 'vote_down';
    case VoteRemoved = 'vote_removed';
    case FavoriteAdd = 'favorite_add';
    case FavoriteRemove = 'favorite_remove';
    case FolderAdd = 'folder_add';
    case FolderRemove = 'folder_remove';
    case Report = 'report';
    case Search = 'search';
    case Publish = 'publish';
    case Update = 'update';
    case FolderCreate = 'folder_create';
    case FolderRename = 'folder_rename';
    case FolderDelete = 'folder_delete';
    case UsernameChange = 'username_change';
    case ThemeChange = 'theme_change';
    case NotificationOpen = 'notification_open';
    case TitleChange = 'title_change';
}
