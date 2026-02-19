<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

class ChatbotScopeGuard
{
    /**
     * @return array{allowed:bool,reason:string}
     */
    public function evaluate(string $message, bool $hasDataMatch): array
    {
        $normalized = $this->normalize($message);

        if ($normalized === '') {
            return [
                'allowed' => false,
                'reason' => 'Mensagem vazia.',
            ];
        }

        foreach ($this->blockedPatterns() as $pattern => $reason) {
            if (preg_match($pattern, $normalized) === 1) {
                return [
                    'allowed' => false,
                    'reason' => $reason,
                ];
            }
        }

        $hasDomainKeyword = Str::contains($normalized, $this->domainKeywords());

        if (! $hasDomainKeyword && ! $hasDataMatch) {
            return [
                'allowed' => false,
                'reason' => 'Fora do escopo da vitrine.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'in_scope',
        ];
    }

    public function refusalMessage(): string
    {
        return 'Posso ajudar apenas com assuntos da loja: produtos, categorias, precos, promocoes, estoque, entrega e contato oficial.';
    }

    private function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/\\s+/', ' ')
            ->trim();
    }

    /**
     * @return array<string,string>
     */
    private function blockedPatterns(): array
    {
        return [
            '/\\b(qual e meu nome|meu nome|quem sou eu|quem e voce|qual e seu nome)\\b/' => 'Tentativa de conversa pessoal.',
            '/\\b(sua idade|quantos anos|signo|religiao|politica|partido|futebol|time|namorada|namorado)\\b/' => 'Assunto pessoal fora do escopo.',
            '/\\b(cpf|rg|senha|numero do cartao|cvv|dados pessoais|telefone pessoal|endereco pessoal)\\b/' => 'Dados pessoais fora do escopo.',
        ];
    }

    /**
     * @return string[]
     */
    private function domainKeywords(): array
    {
        return [
            'produto',
            'produtos',
            'catalogo',
            'categoria',
            'categorias',
            'preco',
            'precos',
            'valor',
            'valores',
            'promocao',
            'promocoes',
            'oferta',
            'ofertas',
            'desconto',
            'estoque',
            'disponivel',
            'entrega',
            'delivery',
            'frete',
            'pagamento',
            'pagamentos',
            'pix',
            'cartao',
            'credito',
            'debito',
            'parcelamento',
            'pedido',
            'comprar',
            'compra',
            'item',
            'itens',
            'loja',
            'empresa',
            'cnpj',
            'telefone',
            'endereco',
            'contato',
            'atendimento',
        ];
    }
}


