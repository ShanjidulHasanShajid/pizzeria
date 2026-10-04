<?php

declare(strict_types=1);

it('shows the home page', function (): void {
    $this->get('/')->assertOk();
});
