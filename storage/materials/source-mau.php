<?php

declare(strict_types=1);

function formatCurrency(int $amount): string
{
    return number_format($amount, 0, ',', '.') . 'đ';
}

echo formatCurrency(99000);
