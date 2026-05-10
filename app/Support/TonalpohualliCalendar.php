<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class TonalpohualliCalendar
{
    /**
     * @var array<int, array<string, string>>
     */
    protected const LEXICOGRAFIA_AZTECA = [
        ['nahua' => 'Cipactli', 'esp' => 'Cocodrilo', 'deidad' => 'Tonacatecuhtli', 'cuerpo' => 'Cabeza y mandibula', 'significado' => 'Inicio, fertilidad y fuerza primordial.'],
        ['nahua' => 'Ehecatl', 'esp' => 'Viento', 'deidad' => 'Quetzalcoatl', 'cuerpo' => 'Respiracion y pulmones', 'significado' => 'Movimiento, impulso y cambio.'],
        ['nahua' => 'Calli', 'esp' => 'Casa', 'deidad' => 'Tepeyollotl', 'cuerpo' => 'Espalda y refugio corporal', 'significado' => 'Resguardo, familia y estabilidad.'],
        ['nahua' => 'Cuetzpalin', 'esp' => 'Lagartija', 'deidad' => 'Huehuecoyotl', 'cuerpo' => 'Extremidades y reflejos', 'significado' => 'Agilidad, adaptacion y vigilancia.'],
        ['nahua' => 'Coatl', 'esp' => 'Serpiente', 'deidad' => 'Chalchiuhtlicue', 'cuerpo' => 'Columna y energia vital', 'significado' => 'Sabiduria, dualidad y transformacion.'],
        ['nahua' => 'Miquiztli', 'esp' => 'Muerte', 'deidad' => 'Tecciztecatl', 'cuerpo' => 'Huesos y descanso', 'significado' => 'Cierre de ciclos, introspeccion y renacimiento.'],
        ['nahua' => 'Mazatl', 'esp' => 'Venado', 'deidad' => 'Tlaloc', 'cuerpo' => 'Piernas y sentido de alerta', 'significado' => 'Sensibilidad, huida y libertad.'],
        ['nahua' => 'Tochtli', 'esp' => 'Conejo', 'deidad' => 'Mayahuel', 'cuerpo' => 'Sistema reproductivo y abdomen', 'significado' => 'Abundancia, placer y expansion.'],
        ['nahua' => 'Atl', 'esp' => 'Agua', 'deidad' => 'Xiuhtecuhtli', 'cuerpo' => 'Rinones y liquidos del cuerpo', 'significado' => 'Fluidez, emocion y purificacion.'],
        ['nahua' => 'Itzcuintli', 'esp' => 'Perro', 'deidad' => 'Mictlantecuhtli', 'cuerpo' => 'Pies y transito', 'significado' => 'Lealtad, guia y proteccion en el camino.'],
        ['nahua' => 'Ozomatli', 'esp' => 'Mono', 'deidad' => 'Xochipilli', 'cuerpo' => 'Manos y coordinacion', 'significado' => 'Juego, creatividad y espontaneidad.'],
        ['nahua' => 'Malinalli', 'esp' => 'Hierba torcida', 'deidad' => 'Patecatl', 'cuerpo' => 'Cabello y tejido fino del cuerpo', 'significado' => 'Resiliencia, enredo y recuperacion.'],
        ['nahua' => 'Acatl', 'esp' => 'Cana', 'deidad' => 'Tezcatlipoca', 'cuerpo' => 'Espina dorsal y postura', 'significado' => 'Rectitud, autoridad y direccion.'],
        ['nahua' => 'Ocelotl', 'esp' => 'Jaguar', 'deidad' => 'Tlazolteotl', 'cuerpo' => 'Corazon y fuerza interna', 'significado' => 'Poder, noche y valentia.'],
        ['nahua' => 'Cuauhtli', 'esp' => 'Aguila', 'deidad' => 'Huitzilopochtli', 'cuerpo' => 'Vista y pecho', 'significado' => 'Elevacion, vision y guerra noble.'],
        ['nahua' => 'Cozcacuauhtli', 'esp' => 'Buitre', 'deidad' => 'Itzpapalotl', 'cuerpo' => 'Garganta y limpieza', 'significado' => 'Depuracion, paciencia y conocimiento de lo viejo.'],
        ['nahua' => 'Ollin', 'esp' => 'Movimiento', 'deidad' => 'Xolotl', 'cuerpo' => 'Corazon y centro del cuerpo', 'significado' => 'Cambio profundo, terremoto y transformacion.'],
        ['nahua' => 'Tecpatl', 'esp' => 'Pedernal', 'deidad' => 'Chicomecoatl', 'cuerpo' => 'Dientes y corte', 'significado' => 'Precision, verdad y decision.'],
        ['nahua' => 'Quiahuitl', 'esp' => 'Lluvia', 'deidad' => 'Tlaloc', 'cuerpo' => 'Piel y sensibilidad al entorno', 'significado' => 'Renovacion, bendicion y riesgo.'],
        ['nahua' => 'Xochitl', 'esp' => 'Flor', 'deidad' => 'Xochiquetzal', 'cuerpo' => 'Rostro y belleza', 'significado' => 'Arte, plenitud y culminacion.'],
    ];

    protected const PIVOTE_COEFICIENTE = 6;

    protected const PIVOTE_INDICE_SIGNO = 19;

    protected const PIVOTE_FECHA = '1996-02-09';

    /**
     * @return array<string, string|int>
     */
    public function resolve(CarbonImmutable $date): array
    {
        $pivotDate = CarbonImmutable::parse(self::PIVOTE_FECHA, 'UTC')->setTime(12, 0);
        $normalizedDate = $date->setTimezone('UTC')->setTime(12, 0);
        $daysElapsed = $pivotDate->diffInDays($normalizedDate, false);

        $signIndex = (($daysElapsed + self::PIVOTE_INDICE_SIGNO) % 20 + 20) % 20;
        $coefficient = (($daysElapsed + self::PIVOTE_COEFICIENTE - 1) % 13 + 13) % 13 + 1;
        $daysBackwards = $coefficient - 1;
        $trecenaIndex = (($signIndex - $daysBackwards) % 20 + 20) % 20;

        $sign = self::LEXICOGRAFIA_AZTECA[$signIndex];
        $trecena = self::LEXICOGRAFIA_AZTECA[$trecenaIndex];

        return [
            'coeficiente' => $coefficient,
            'tonalli' => $coefficient.' - '.$sign['nahua'],
            'nahua' => $sign['nahua'],
            'espanol' => $sign['esp'],
            'deidad' => $sign['deidad'],
            'cuerpo' => $sign['cuerpo'],
            'significado' => $sign['significado'],
            'trecena' => '1-'.$trecena['nahua'].' ('.$trecena['esp'].')',
        ];
    }
}
