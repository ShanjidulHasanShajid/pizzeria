<?php

declare(strict_types=1);

namespace App\Modules\Customization\Presentation\Livewire;

use App\Modules\Customization\Application\DTOs\AddToCartInput;
use App\Modules\Customization\Application\DTOs\ConfigurableVariant;
use App\Modules\Customization\Application\DTOs\PricedLineView;
use App\Modules\Customization\Application\DTOs\ProductConfiguration;
use App\Modules\Customization\Application\DTOs\QuoteLineInput;
use App\Modules\Customization\Application\Ports\ShoppingCart;
use App\Modules\Customization\Application\Queries\ProductConfigurationQuery;
use App\Modules\Customization\Application\UseCases\QuoteLine;
use App\Modules\Customization\Domain\Exceptions\PricingFailed;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\ValueObjects\Quantity;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Size, options, quantity and note of one product, with the live price (10.14, 10.15).
 * It holds only what the customer chose; every price and every rule comes from the QuoteLine use case.
 * Used as <livewire:customization.product-configurator :product-id="..." :variant-id="..." />.
 */
final class ProductConfigurator extends Component
{
    /** Locked: the browser cannot change which product this component works on. */
    #[Locked]
    public int $productId;

    /** A text, because that is what a radio button sends. */
    public string $variantId = '';

    /** @var array<int, mixed> option group id => chosen option id or "" (groups with at most one pick) */
    public array $single = [];

    /** @var array<int, mixed> option group id => chosen option ids (groups with several picks) */
    public array $multiple = [];

    /** Locked: it changes only through more() and less(). */
    #[Locked]
    public int $quantity = Quantity::MIN;

    /** For the kitchen. Goes into the cart with the item. */
    public string $note = '';

    /** Why the cart refused the item (full, too many of it ...). */
    private ?string $cartProblem = null;

    private ProductConfigurationQuery $query;

    private QuoteLine $quoteLine;

    private ?ProductConfiguration $configuration = null;

    public function boot(ProductConfigurationQuery $query, QuoteLine $quoteLine): void
    {
        $this->query = $query;
        $this->quoteLine = $quoteLine;
    }

    public function mount(int $productId, int $variantId): void
    {
        $this->productId = $productId;
        $this->variantId = (string) $variantId;

        foreach ($this->configuration()->groups ?? [] as $group) {
            if ($group->maximum === 1) {
                $this->single[$group->optionGroupId] = '';
            } else {
                $this->multiple[$group->optionGroupId] = [];
            }
        }
    }

    /** Another size: picks that are not offered for it are taken away. */
    public function updatedVariantId(): void
    {
        $sizeTierId = $this->variant()?->sizeTierId;

        foreach ($this->configuration()->groups ?? [] as $group) {
            $offered = [];

            foreach ($group->options as $option) {
                if ($option->priceFor($sizeTierId) !== null) {
                    $offered[] = $option->id;
                }
            }

            $kept = array_map(strval(...), array_values(array_intersect($this->picks($group->optionGroupId, $group->maximum), $offered)));

            if ($group->maximum === 1) {
                $this->single[$group->optionGroupId] = $kept[0] ?? '';
            } else {
                $this->multiple[$group->optionGroupId] = $kept;
            }
        }
    }

    public function more(): void
    {
        $this->quantity = min(Quantity::MAX, $this->quantity + 1);
    }

    public function less(): void
    {
        $this->quantity = max(Quantity::MIN, $this->quantity - 1);
    }

    /**
     * The picks stay, because another one like this is the likely next wish; quantity and note start again.
     * The mini cart opens on "cart-item-added", the counters read again on "cart-changed".
     */
    public function addToCart(ShoppingCart $cart): void
    {
        try {
            $cart->add(new AddToCartInput($this->productId, $this->selectedVariantId(), $this->choices(), $this->quantity, $this->note));
        } catch (DomainException $refused) {
            $this->cartProblem = $refused->getMessage();

            return;
        }

        $this->quantity = Quantity::MIN;
        $this->note = '';
        $this->dispatch('cart-changed');
        $this->dispatch('cart-item-added');
    }

    public function render(): View
    {
        $configuration = $this->configuration();
        $choices = $this->choices();
        $line = null;
        $problem = null;
        $problemGroupId = null;

        try {
            $line = $configuration === null ? null : $this->quote($choices);
        } catch (PricingFailed $refused) {
            $problem = $refused->getMessage();
            $problemGroupId = $refused->optionGroupId;
        } catch (DomainException $refused) {
            $problem = $refused->getMessage();
        }

        return view('customization::livewire.product-configurator', [
            'configuration' => $configuration,
            'variant' => $this->variant(),
            'choices' => $choices,
            'line' => $line,
            'problem' => $problem,
            'problemGroupId' => $problemGroupId,
            'cartProblem' => $this->cartProblem,
        ]);
    }

    /**
     * @param  array<int, list<int>>  $choices
     */
    private function quote(array $choices): PricedLineView
    {
        return $this->quoteLine->handle(new QuoteLineInput($this->productId, $this->selectedVariantId(), $choices, $this->quantity));
    }

    /**
     * @return array<int, list<int>> option group id => chosen option ids, for the groups of this product
     */
    private function choices(): array
    {
        $choices = [];

        foreach ($this->configuration()->groups ?? [] as $group) {
            $choices[$group->optionGroupId] = $this->picks($group->optionGroupId, $group->maximum);
        }

        return $choices;
    }

    /**
     * What stands in the browser's fields is never trusted: only whole numbers, each of them once.
     *
     * @return list<int>
     */
    private function picks(int $optionGroupId, int $maximum): array
    {
        $given = $maximum === 1
            ? [$this->single[$optionGroupId] ?? '']
            : (array) ($this->multiple[$optionGroupId] ?? []);

        $ids = [];

        foreach ($given as $value) {
            if ((is_string($value) || is_int($value)) && preg_match('/^[1-9]\d{0,17}$/D', (string) $value) === 1) {
                $ids[(int) $value] = (int) $value;
            }
        }

        return array_values($ids);
    }

    private function variant(): ?ConfigurableVariant
    {
        return $this->configuration()?->variant($this->selectedVariantId());
    }

    private function selectedVariantId(): int
    {
        return preg_match('/^[1-9]\d{0,17}$/D', $this->variantId) === 1 ? (int) $this->variantId : 0;
    }

    /** Read once per request. */
    private function configuration(): ?ProductConfiguration
    {
        return $this->configuration ??= $this->query->forProduct($this->productId);
    }
}
