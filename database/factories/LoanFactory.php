<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LoanFactory extends Factory
{
    public function definition(): array
    {
        // 1. Generate basic numbers
        $amount = $this->faker->randomFloat(2, 1000, 50000);
        $rate = $this->faker->randomFloat(2, 5, 20); // 5% to 20% interest
        $tenure = $this->faker->numberBetween(3, 36); // 3 to 36 months

        // 2. Calculate Math (Simple Interest Formula for seeding context)
        // Interest = Principal * (Rate/100) * (Months/12)
        $interestAmount = $amount * ($rate / 100) * ($tenure / 12);
        $totalPayment = $amount + $interestAmount;
        $emi = $totalPayment / $tenure;

        // 3. Status Logic
        $status = $this->faker->randomElement(['pending', 'approved', 'rejected', 'completed']);

        // Handle Dates based on status
        $disbursedAt = null;
        $repayedAt = null;
        $dueDate = null;

        if (in_array($status, ['approved', 'completed'])) {
            $disbursedAt = $this->faker->dateTimeBetween('-1 year', 'now');
            $dueDate = (clone $disbursedAt)->modify("+$tenure months");
        }

        if ($status === 'completed') {
            $repayedAt = $this->faker->dateTimeBetween($disbursedAt, 'now');
        }

        return [
            'title' => $this->faker->words(3, true) . ' Loan',
            'loan_amount' => $amount,
            'interest_rate' => $rate,
            'tenure_months' => $tenure,
            'monthly_emi' => $emi,
            'total_interest' => $interestAmount,
            'total_payment' => $totalPayment,
            'loan_reason' => $this->faker->sentence(),
            'loan_collateral' => $this->faker->sentence(),
            'status' => $status,
            'disbursed_at' => $disbursedAt,
            'repayed_at' => $repayedAt,
            'due_date' => $dueDate,
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }
}
