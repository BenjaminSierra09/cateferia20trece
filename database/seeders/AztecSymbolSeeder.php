<?php

namespace Database\Seeders;

use App\Models\AztecSymbol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AztecSymbolSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->symbols() as $index => $symbol) {
            AztecSymbol::query()->updateOrCreate([
                'sort_order' => $index + 1,
            ], [
                'name' => $symbol['name'],
                'slug' => Str::slug($symbol['name']),
                'spanish_name' => $symbol['spanish_name'],
                'deity' => $symbol['deity'],
                'body_area' => $symbol['body_area'],
                'meaning' => $symbol['meaning'],
                'service_description' => $symbol['service_description'],
                'customer_greeting' => $symbol['customer_greeting'],
                'taste_profile' => $symbol['taste_profile'],
                'recommended_items' => $symbol['recommended_items'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{
     *     name: string,
     *     spanish_name: string,
     *     deity: string,
     *     body_area: string,
     *     meaning: string,
     *     service_description: string,
     *     customer_greeting: string,
     *     taste_profile: string,
     *     recommended_items: list<string>
     * }>
     */
    private function symbols(): array
    {
        return [
            ['name' => 'Cipactli', 'spanish_name' => 'Cocodrilo', 'deity' => 'Tonacatecuhtli', 'body_area' => 'Cabeza y mandíbula', 'meaning' => 'Inicio, fertilidad y fuerza primordial.', 'service_description' => 'Recibir con energía y ofrecer algo contundente.', 'customer_greeting' => 'Hoy empezamos fuerte. Te recomiendo algo con carácter.', 'taste_profile' => 'Sabores intensos, tostados y con cuerpo.', 'recommended_items' => ['Espresso doble', 'Cold brew intenso', 'Pan recién horneado']],
            ['name' => 'Ehecatl', 'spanish_name' => 'Viento', 'deity' => 'Quetzalcóatl', 'body_area' => 'Respiración y pulmones', 'meaning' => 'Movimiento, impulso y cambio.', 'service_description' => 'Atención rápida, fresca y con opciones abiertas.', 'customer_greeting' => '¿Buscas algo ligero o algo que te impulse?', 'taste_profile' => 'Bebidas ligeras, limpias o cítricas.', 'recommended_items' => ['Americano', 'Café filtrado', 'Bebida fría con cítricos']],
            ['name' => 'Calli', 'spanish_name' => 'Casa', 'deity' => 'Tepeyóllotl', 'body_area' => 'Espalda y refugio corporal', 'meaning' => 'Resguardo, familia y estabilidad.', 'service_description' => 'Dar refugio, calma y un tono cálido.', 'customer_greeting' => 'Siéntete en casa. Aquí va algo reconfortante.', 'taste_profile' => 'Notas lácteas, especias suaves y bebidas calientes.', 'recommended_items' => ['Latte', 'Chocolate', 'Café con canela']],
            ['name' => 'Cuetzpalin', 'spanish_name' => 'Lagartija', 'deity' => 'Huehuecóyotl', 'body_area' => 'Extremidades y reflejos', 'meaning' => 'Agilidad, adaptación y vigilancia.', 'service_description' => 'Servicio ágil y práctico, ideal para llevar.', 'customer_greeting' => 'Algo rápido, vivo y sin complicaciones.', 'taste_profile' => 'Sabores claros, formatos para llevar y bebidas con chispa.', 'recommended_items' => ['Cappuccino para llevar', 'Espresso tonic']],
            ['name' => 'Coatl', 'spanish_name' => 'Serpiente', 'deity' => 'Chalchiuhtlicue', 'body_area' => 'Columna y energía vital', 'meaning' => 'Sabiduría, dualidad y transformación.', 'service_description' => 'Hablar con calma y resaltar capas de sabor.', 'customer_greeting' => 'Este café cambia conforme lo pruebas.', 'taste_profile' => 'Cafés complejos, especiados y con evolución aromática.', 'recommended_items' => ['Café de especialidad', 'Método V60', 'Bebida con notas especiadas']],
            ['name' => 'Miquiztli', 'spanish_name' => 'Muerte', 'deity' => 'Tecciztécatl', 'body_area' => 'Huesos y descanso', 'meaning' => 'Cierre de ciclos, introspección y renacimiento.', 'service_description' => 'Dar espacio y un trato discreto.', 'customer_greeting' => 'Algo tranquilo para cerrar o reiniciar el día.', 'taste_profile' => 'Bebidas sobrias, profundas y de dulzor moderado.', 'recommended_items' => ['Café negro', 'Té oscuro', 'Postre sobrio']],
            ['name' => 'Mazatl', 'spanish_name' => 'Venado', 'deity' => 'Tláloc', 'body_area' => 'Piernas y sentido de alerta', 'meaning' => 'Sensibilidad, huida y libertad.', 'service_description' => 'Ser suave, sin presionar, con opciones ligeras.', 'customer_greeting' => 'Algo delicado, fresco y fácil de disfrutar.', 'taste_profile' => 'Sabores suaves, frutales y de baja intensidad.', 'recommended_items' => ['Latte suave', 'Infusión herbal', 'Pan con fruta']],
            ['name' => 'Tochtli', 'spanish_name' => 'Conejo', 'deity' => 'Mayahuel', 'body_area' => 'Sistema reproductivo y abdomen', 'meaning' => 'Abundancia, placer y expansión.', 'service_description' => 'Trato alegre y una propuesta indulgente.', 'customer_greeting' => 'Hoy se vale disfrutar. Algo dulce te quedaría bien.', 'taste_profile' => 'Dulce, cremoso y de antojo.', 'recommended_items' => ['Moka', 'Frappé', 'Pastel']],
            ['name' => 'Atl', 'spanish_name' => 'Agua', 'deity' => 'Xiuhtecuhtli', 'body_area' => 'Riñones y líquidos del cuerpo', 'meaning' => 'Fluidez, emoción y purificación.', 'service_description' => 'Ofrecer algo fresco y un tono sereno.', 'customer_greeting' => 'Vamos con algo fresco y fluido.', 'taste_profile' => 'Bebidas frías, herbales o con leche vegetal.', 'recommended_items' => ['Cold brew', 'Té helado', 'Bebida con leche vegetal']],
            ['name' => 'Itzcuintli', 'spanish_name' => 'Perro', 'deity' => 'Mictlantecuhtli', 'body_area' => 'Pies y tránsito', 'meaning' => 'Lealtad, guía y protección en el camino.', 'service_description' => 'Guiar con confianza y recordar lo habitual.', 'customer_greeting' => 'Ya sé por dónde va tu camino cafetero.', 'taste_profile' => 'Preferencias conocidas, confiables y fáciles de repetir.', 'recommended_items' => ['Su bebida habitual', 'Recomendación confiable']],
            ['name' => 'Ozomatli', 'spanish_name' => 'Mono', 'deity' => 'Xochipilli', 'body_area' => 'Manos y coordinación', 'meaning' => 'Juego, creatividad y espontaneidad.', 'service_description' => 'Hacer una recomendación divertida o sorpresa.', 'customer_greeting' => 'Tengo algo raro pero bueno. ¿Te animas?', 'taste_profile' => 'Combinaciones experimentales, coloridas y de temporada.', 'recommended_items' => ['Bebida de temporada', 'Combinación experimental']],
            ['name' => 'Malinalli', 'spanish_name' => 'Hierba torcida', 'deity' => 'Patécatl', 'body_area' => 'Cabello y tejido fino del cuerpo', 'meaning' => 'Resiliencia, enredo y recuperación.', 'service_description' => 'Tono empático y restaurador.', 'customer_greeting' => 'Algo para desenredar el día.', 'taste_profile' => 'Infusiones, especias suaves y perfiles restauradores.', 'recommended_items' => ['Infusión', 'Chai', 'Café suave']],
            ['name' => 'Acatl', 'spanish_name' => 'Caña', 'deity' => 'Tezcatlipoca', 'body_area' => 'Espina dorsal y postura', 'meaning' => 'Rectitud, autoridad y dirección.', 'service_description' => 'Atención clara, ordenada y directa.', 'customer_greeting' => 'Algo preciso, bien balanceado y sin rodeos.', 'taste_profile' => 'Bebidas balanceadas, limpias y consistentes.', 'recommended_items' => ['Espresso', 'Flat white', 'Café filtrado limpio']],
            ['name' => 'Ocelotl', 'spanish_name' => 'Jaguar', 'deity' => 'Tlazoltéotl', 'body_area' => 'Corazón y fuerza interna', 'meaning' => 'Poder, noche y valentía.', 'service_description' => 'Servir con elegancia y algo de intensidad.', 'customer_greeting' => 'Hoy vienes con energía intensa y poderosa.', 'taste_profile' => 'Cacao, amargor elegante y café concentrado.', 'recommended_items' => ['Moka amargo', 'Espresso doble']],
            ['name' => 'Cuauhtli', 'spanish_name' => 'Águila', 'deity' => 'Huitzilopochtli', 'body_area' => 'Vista y pecho', 'meaning' => 'Elevación, visión y guerra noble.', 'service_description' => 'Trato decidido y enfocado.', 'customer_greeting' => 'Algo para ver claro y subir el ritmo.', 'taste_profile' => 'Acidez brillante, claridad y energía.', 'recommended_items' => ['Americano', 'Pour-over brillante']],
            ['name' => 'Cozcacuauhtli', 'spanish_name' => 'Buitre', 'deity' => 'Itzpapálotl', 'body_area' => 'Garganta y limpieza', 'meaning' => 'Depuración, paciencia y conocimiento de lo viejo.', 'service_description' => 'Servicio pausado y sabores asentados.', 'customer_greeting' => 'Algo con historia, sobrio y bien asentado.', 'taste_profile' => 'Tueste medio u oscuro, pan rústico y notas maduras.', 'recommended_items' => ['Café de tueste medio', 'Café de tueste oscuro', 'Pan rústico']],
            ['name' => 'Ollin', 'spanish_name' => 'Movimiento', 'deity' => 'Xólotl', 'body_area' => 'Corazón y centro del cuerpo', 'meaning' => 'Cambio profundo, terremoto y transformación.', 'service_description' => 'Invitar a mover la rutina con algo nuevo.', 'customer_greeting' => 'Hoy toca mover la rutina.', 'taste_profile' => 'Novedades, métodos alternativos y cafés experimentales.', 'recommended_items' => ['Bebida nueva del menú', 'Café experimental']],
            ['name' => 'Tecpatl', 'spanish_name' => 'Pedernal', 'deity' => 'Chicomecóatl', 'body_area' => 'Dientes y corte', 'meaning' => 'Precisión, verdad y decisión.', 'service_description' => 'Atención rápida, limpia y precisa.', 'customer_greeting' => 'Sin rodeos: café limpio, fuerte y bien hecho.', 'taste_profile' => 'Café corto, directo y de sabor definido.', 'recommended_items' => ['Cortado', 'Espresso']],
            ['name' => 'Quiahuitl', 'spanish_name' => 'Lluvia', 'deity' => 'Tláloc', 'body_area' => 'Piel y sensibilidad al entorno', 'meaning' => 'Renovación, bendición y riesgo.', 'service_description' => 'Tono amable y reconfortante.', 'customer_greeting' => 'Algo que caiga suave y renueve el ánimo.', 'taste_profile' => 'Vainilla, flores y bebidas calientes suaves.', 'recommended_items' => ['Latte con vainilla', 'Té floral', 'Café caliente']],
            ['name' => 'Xochitl', 'spanish_name' => 'Flor', 'deity' => 'Xochiquétzal', 'body_area' => 'Rostro y belleza', 'meaning' => 'Arte, plenitud y culminación.', 'service_description' => 'Cuidar presentación, aroma y detalle visual.', 'customer_greeting' => 'Hoy toca algo bello, aromático y bien presentado.', 'taste_profile' => 'Florales, vainilla, lavanda y bebidas bonitas.', 'recommended_items' => ['Latte con lavanda', 'Latte con vainilla', 'Bebida floral']],
        ];
    }
}
