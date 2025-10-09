<?php 
namespace App\Service;



class PaymentService
{
    public function __construct(
        
    ) {
    }

 

    public function calculateTax(float $cost): float
    {
        // Define the fixed tax percentage
        $taxPercentage = 0.05;

        // Calculate the tax amount
        $tax = $cost * $taxPercentage;

        return $tax;
    }
  
}

?>