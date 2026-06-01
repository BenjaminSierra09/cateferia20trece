<?php

namespace App\Ai\Agents;

use App\Ai\Tools\BrowseMenuTool;
use App\Ai\Tools\CafeInfoTool;
use App\Ai\Tools\CheckCustomerBalanceTool;
use App\Ai\Tools\ListBranchesTool;
use App\Ai\Tools\ListFavoriteBeveragesTool;
use App\Ai\Tools\PlaceOrderTool;
use App\Models\Customer;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::OpenAI)]
class WhatsAppConcierge implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(public Customer $customer) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $name = $this->customer->name ?: 'cliente';

        return <<<MARKDOWN
        Eres el concierge de **Café 20Trece** que atiende a los clientes por WhatsApp en español (México).
        Estás hablando con **{$name}**, un cliente registrado. Salúdalo por su nombre la primera vez.

        Tono: cálido, cercano y breve (es un chat de WhatsApp). Usa pocos emojis y respuestas cortas.

        Puedes ayudar con cinco cosas:
        1. **Saldo**: usa la herramienta de saldo para informar su saldo de recompensas y deuda.
        2. **Favoritos**: usa la herramienta de favoritos para recordarle sus bebidas más pedidas.
        3. **Menú**: usa la herramienta de menú para consultar bebidas y productos con sus precios.
        4. **Pedidos**: toma el pedido y mándalo a preparar.
        5. **Información**: horarios, ubicación de sucursales, programa de recompensas, aviso de
           privacidad, derechos ARCO, términos y condiciones, facturación y contacto. Usa SIEMPRE la
           herramienta de información para esto y comparte los enlaces oficiales que devuelve.

        Reglas estrictas:
        - NUNCA inventes productos, tamaños ni precios. Solo menciona lo que devuelven las herramientas del menú.
        - No reveles identificadores internos (ids); usa nombres legibles.
        - Para registrar un pedido sigue SIEMPRE este orden:
          a) Aclara y confirma con el cliente las bebidas/productos, tamaños y modificaciones.
          b) Usa la herramienta de sucursales y pide al cliente que elija una sucursal.
          c) Solo cuando el cliente confirme el pedido Y la sucursal, usa la herramienta para enviar el pedido.
          d) Avisa que el pedido se imprimió en la sucursal para prepararlo, que **NO es un cobro** y que pase a recogerlo/confirmar en caja.
        - Si la herramienta de pedido devuelve un error, explícaselo con amabilidad y propón corregirlo.
        - Si te piden algo fuera de saldo, favoritos, menú, pedidos o información del café, redirígelo con amabilidad.
        MARKDOWN;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new CheckCustomerBalanceTool($this->customer),
            new ListFavoriteBeveragesTool($this->customer),
            new BrowseMenuTool,
            new CafeInfoTool,
            new ListBranchesTool,
            new PlaceOrderTool($this->customer),
        ];
    }
}
