<?php

declare(strict_types=1);

use App\Support\TemplateVariables;

test('extrae las variables distintas en orden de primera aparición', function (): void {
    expect(TemplateVariables::extract('Hola {{nombre}}, vives en {{ciudad}}. Adiós {{nombre}}.'))->toBe(['nombre', 'ciudad']);
});

test('acepta letras con acentos, números y guion bajo', function (): void {
    expect(TemplateVariables::extract('{{año}} {{n_2}} {{ñandú}} {{día-uno}}'))->toBe(['año', 'n_2', 'ñandú']);
});

test('admite espacios dentro de las llaves y los trata como la misma variable', function (): void {
    expect(TemplateVariables::extract('{{ nombre }} {{nombre}} {{  nombre}}'))->toBe(['nombre']);
});

test('agrupa sin distinguir mayúsculas y conserva la etiqueta de la primera aparición', function (): void {
    expect(TemplateVariables::extract('{{Nombre}} y {{nombre}} y {{NOMBRE}} y {{Ciudad}}'))->toBe(['Nombre', 'Ciudad']);
});

test('un nombre de más de 30 caracteres es texto normal y uno de 30 es variable', function (): void {
    $thirty = str_repeat('a', 30);
    $thirtyOne = str_repeat('a', 31);

    expect(TemplateVariables::extract('{{'.$thirty.'}}'))->toBe([$thirty])
        ->and(TemplateVariables::extract('{{'.$thirtyOne.'}}'))->toBe([]);
});

test('las llaves vacías, con otros símbolos o sin cerrar no son variables', function (): void {
    expect(TemplateVariables::extract('{{}} {{ }} {{a b}} {{a-b}} {nombre} {{nombre}'))->toBe([]);
});
