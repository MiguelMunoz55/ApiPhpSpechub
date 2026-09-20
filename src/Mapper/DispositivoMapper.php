<?php
declare(strict_types=1);

namespace SpecHub\Api\Mapper;

use stdClass;

/**
 * Convierte filas de la BD al JSON "enriquecido" que consume el front-end
 * (mismo contrato que DispositivoDTO.java / enrichDevice() de DataContext.jsx):
 * dispositivo + marca + tipo + specs + imagen + comentarios + promedio.
 */
final class DispositivoMapper
{
    /**
     * @param array<string,mixed>       $fila        fila de dispositivo con marca y tipo (JOIN)
     * @param list<array<string,mixed>> $specs       filas de especificacion_dispositivo (ya ordenadas)
     * @param list<array<string,mixed>> $imagenes    filas de imagen_dispositivo (ya ordenadas)
     * @param list<array<string,mixed>> $comentarios filas de comentario (más recientes primero)
     * @return array<string,mixed>
     */
    public function aDto(array $fila, array $specs, array $imagenes, array $comentarios): array
    {
        $mapaSpecs = [];
        foreach ($specs as $s) {
            $mapaSpecs[(string) $s['clave']] = (string) $s['valor'];
        }

        $comments = array_map(fn(array $c): array => [
            'id'       => (int) $c['id'],
            'deviceId' => (int) $c['dispositivo_id'],
            'author'   => $c['nombre_autor'],
            'rating'   => (int) $c['calificacion'],
            'content'  => $c['contenido'],
            'date'     => substr((string) $c['creado_en'], 0, 10),
        ], $comentarios);

        $promedio = null;
        if ($comments !== []) {
            $promedio = round(array_sum(array_column($comments, 'rating')) / count($comments), 2);
        }

        return [
            'id'               => (int) $fila['id'],
            'name'             => $fila['nombre'],
            'brandId'          => (int) $fila['marca_id'],
            'typeId'           => (int) $fila['tipo_id'],
            'releaseDate'      => (string) $fila['fecha_lanzamiento'],
            'price'            => (float) $fila['precio'],
            'shortDescription' => $fila['descripcion_corta'],
            'review'           => $fila['resena'],
            'imageTone'        => $fila['tono_imagen'],
            'imageUrl'         => $imagenes[0]['url_imagen'] ?? null,
            // (object) garantiza "{}" y no "[]" cuando no hay especificaciones
            'specs'            => $mapaSpecs === [] ? new stdClass() : (object) $mapaSpecs,
            'brand'            => ['id' => (int) $fila['marca_id'], 'name' => $fila['marca_nombre'], 'country' => $fila['marca_pais']],
            'type'             => ['id' => (int) $fila['tipo_id'], 'name' => $fila['tipo_nombre'], 'slug' => $fila['tipo_slug']],
            'comments'         => $comments,
            'averageRating'    => $promedio,
            'commentCount'     => count($comments),
        ];
    }
}
