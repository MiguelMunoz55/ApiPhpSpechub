<?php
declare(strict_types=1);

namespace SpecHub\Api\Validation;

/** Reglas del cuerpo de POST/PUT /api/brands (equivalente a MarcaRequestDTO.java). */
final class MarcaValidator
{
    /**
     * @param  array<string,mixed> $input
     * @return array{name:string, country:?string}
     */
    public function validar(array $input): array
    {
        $v = new Validator($input);
        $datos = [
            'name'    => $v->texto('name', 'El nombre de la marca es obligatorio', 'El nombre', 80),
            'country' => $v->textoOpcional('country', 'El país', 60),
        ];
        $v->lanzarSiHayErrores();
        return $datos;
    }
}
