<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Products\ProductWriteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Popula categorias e pelo menos 30 produtos de varejo realistas.
 * Seguro para reexecução: não duplica por slug/barcode.
 *
 *   php artisan db:seed --class=DemoCatalogSeeder
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->authenticateForWrite();

        $categories = collect([
            ['name' => 'Bebidas', 'slug' => 'bebidas'],
            ['name' => 'Mercearia', 'slug' => 'mercearia'],
            ['name' => 'Laticínios', 'slug' => 'laticinios'],
            ['name' => 'Higiene', 'slug' => 'higiene'],
            ['name' => 'Limpeza', 'slug' => 'limpeza'],
            ['name' => 'Padaria', 'slug' => 'padaria'],
            ['name' => 'Hortifruti', 'slug' => 'hortifruti'],
            ['name' => 'Snacks', 'slug' => 'snacks'],
        ])->mapWithKeys(function (array $item, int $index) {
            $category = Category::query()->firstOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'context' => 'catalog',
                    'order' => $index + 1,
                    'status' => true,
                ]
            );

            return [$item['slug'] => $category];
        });

        $products = [
            ['Arroz branco tipo 1 5kg', 'mercearia', 24.90, null, false, 40, '7891000100001'],
            ['Feijão carioca 1kg', 'mercearia', 8.49, 7.49, true, 55, '7891000100002'],
            ['Açúcar cristal 1kg', 'mercearia', 4.79, null, false, 60, '7891000100003'],
            ['Café torrado e moído 500g', 'mercearia', 18.90, 16.90, true, 35, '7891000100004'],
            ['Óleo de soja 900ml', 'mercearia', 7.99, null, false, 48, '7891000100005'],
            ['Macarrão espaguete 500g', 'mercearia', 4.29, null, false, 70, '7891000100006'],
            ['Molho de tomate 340g', 'mercearia', 3.49, null, false, 65, '7891000100007'],
            ['Sal refinado 1kg', 'mercearia', 2.49, null, false, 80, '7891000100008'],
            ['Leite integral 1L', 'laticinios', 5.49, 4.99, true, 90, '7891000100009'],
            ['Iogurte natural 170g', 'laticinios', 3.29, null, false, 45, '7891000100010'],
            ['Queijo mussarela fatiado 200g', 'laticinios', 14.90, null, false, 25, '7891000100011'],
            ['Manteiga com sal 200g', 'laticinios', 12.50, null, false, 30, '7891000100012'],
            ['Refrigerante cola 2L', 'bebidas', 9.99, 8.49, true, 50, '7891000100013'],
            ['Água mineral sem gás 1,5L', 'bebidas', 2.99, null, false, 100, '7891000100014'],
            ['Suco de uva integral 1L', 'bebidas', 12.90, null, false, 28, '7891000100015'],
            ['Cerveja pilsen lata 350ml', 'bebidas', 3.79, 3.29, true, 120, '7891000100016'],
            ['Energético 250ml', 'bebidas', 8.50, null, false, 40, '7891000100017'],
            ['Sabonete em barra 90g', 'higiene', 2.49, null, false, 75, '7891000100018'],
            ['Shampoo 350ml', 'higiene', 16.90, 14.90, true, 32, '7891000100019'],
            ['Creme dental 90g', 'higiene', 5.99, null, false, 60, '7891000100020'],
            ['Papel higiênico 12 rolos', 'higiene', 22.90, null, false, 35, '7891000100021'],
            ['Detergente líquido 500ml', 'limpeza', 2.79, null, false, 70, '7891000100022'],
            ['Sabão em pó 1kg', 'limpeza', 15.90, 13.90, true, 40, '7891000100023'],
            ['Amaciante 2L', 'limpeza', 18.50, null, false, 28, '7891000100024'],
            ['Água sanitária 1L', 'limpeza', 4.49, null, false, 50, '7891000100025'],
            ['Pão francês (kg)', 'padaria', 14.90, null, false, 20, '7891000100026'],
            ['Pão de forma integral 500g', 'padaria', 9.49, null, false, 30, '7891000100027'],
            ['Bolo caseiro fatia', 'padaria', 6.50, null, false, 18, '7891000100028'],
            ['Banana prata (kg)', 'hortifruti', 6.99, null, false, 40, '7891000100029'],
            ['Tomate (kg)', 'hortifruti', 8.49, 7.49, true, 35, '7891000100030'],
            ['Alface crespa unidade', 'hortifruti', 3.49, null, false, 25, '7891000100031'],
            ['Batata inglesa (kg)', 'hortifruti', 5.99, null, false, 45, '7891000100032'],
            ['Batata chips 120g', 'snacks', 9.90, 8.50, true, 40, '7891000100033'],
            ['Biscoito recheado 140g', 'snacks', 3.99, null, false, 55, '7891000100034'],
            ['Amendoim torrado 150g', 'snacks', 7.49, null, false, 30, '7891000100035'],
        ];

        $writer = app(ProductWriteService::class);
        $created = 0;

        foreach ($products as [$name, $categorySlug, $price, $promo, $onSale, $stock, $barcode]) {
            $slug = Str::slug($name);
            if (Product::query()->where(fn ($q) => $q->where('slug', $slug)->orWhere('barcode', $barcode))->exists()) {
                continue;
            }

            $writer->save([
                'name' => $name,
                'description' => $name.' — item de catálogo demonstrativo do Vendix.',
                'slug' => $slug,
                'sku' => 'SKU-'.substr($barcode, -6),
                'barcode' => $barcode,
                'category_id' => $categories[$categorySlug]->id,
                'price' => $price,
                'promotional_price' => $promo,
                'is_on_sale' => $onSale,
                'stock' => $stock,
                'min_stock' => max(5, (int) floor($stock * 0.15)),
                'is_active' => true,
                'stock_reason' => 'Saldo inicial do catálogo demonstrativo',
            ]);
            $created++;
        }

        $this->command?->info("DemoCatalogSeeder: {$created} produtos criados (pulados os que já existiam).");
    }

    private function authenticateForWrite(): void
    {
        // ProductWriteService e StockService exigem Gate; no console não há sessão.
        Gate::before(function (?User $user, string $ability) {
            return true;
        });

        $needed = ['create-products', 'edit-products', 'create-stock-movements'];
        foreach ($needed as $name) {
            Permission::findOrCreate($name, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::query()->orderBy('id')->first();
        if (! $user) {
            throw new \RuntimeException('DemoCatalogSeeder precisa de ao menos um usuário no banco (ex.: admin).');
        }

        $user->givePermissionTo($needed);
        Auth::login($user);
    }
}
