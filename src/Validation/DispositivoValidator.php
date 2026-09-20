<?php
declare(strict_types=1);

namespace SpecHub\Api\Validation;

/**
 * Reglas del cuerpo de POST/PUT /api/devices. Equivalente a DispositivoRequestDTO.java
 * (mismos campos y mensajes), más límites de largo alineados con las columnas de la BD.
 */
final class DispositivoValidator
{
    private const MAX_SPECS = 50;

    /**
     * @param  array<string,mixed> $input
     * @return array{name:string, brandId:int, typeId:int, releaseDate:string, price:string,
     *               shortDescription:?string, review:?string, imageTone:?string, specs:array<string,string>}
     */
    public function validar(array $input): array
    {
        $v = new Validator($input);

        $datos = [
            'name'             => $v->texto('name', 'El nombre del dispositivo es obligatorio', 'El nombre', 120),
            'brandId'          => $v->idPositivo('brandId', 'La marca es obligatoria', 'La marca'),
            'typeId'           => $v->idPositivo('typeId', 'El tipo de dispositivo es obligatorio', 'El tipo de dispositivo'),
            'releaseDate'      => $v->fecha('releaseDate', 'La fecha de lanzamiento es obligatoria', 'La fecha de lanzamiento'),
            'price'            => $v->monto('price', 'El precio es obligatorio', 'El precio no puede ser negativo', 'El precio'),
            'shortDescription' => $v->textoOpcional('shortDescription', 'La descripción corta', 200),
            'review'           => $v->textoOpcional('review', 'La reseña', 20000),
            'imageTone'        => $v->textoOpcional('imageTone', 'El tono de imagen', 40),
            'specs'            => $this->specs($v),
        ];

        $v->lanzarSiHayErrores();
        return $datos;
    }

    /** @return array<string,string> */
    private function specs(Validator $v): array
    {
        $raw = $v->valor('specs');
        if ($raw === null || $raw === []) {
            return [];
        }
        if (!is_array($raw) || array_is_list($raw)) {
            $v->error('specs', 'La ficha técnica debe ser un objeto { "Clave": "Valor" }');
            return [];
        }
        if (count($raw) > self::MAX_SPECS) {
            $v->error('specs', 'La ficha técnica admite máximo ' . self::MAX_SPECS . ' especificaciones');
            return [];
        }

        $limpias = [];
        foreach ($raw as $clave => $valor) {
            $clave = trim((string) $clave);
            if ($clave === '' || mb_strlen($clave) > 80) {
                $v->error('specs', 'Cada clave de la ficha técnica debe tener entre 1 y 80 caracteres');
                return [];
            }
            if (!is_scalar($valor) || is_bool($valor) || trim((string) $valor) === '' || mb_strlen(trim((string) $valor)) > 200) {
                $v->error('specs', "El valor de la especificación '$clave' debe ser un texto de 1 a 200 caracteres");
                return [];
            }
            $limpias[$clave] = trim((string) $valor);
        }
        return $limpias;
    }
}
