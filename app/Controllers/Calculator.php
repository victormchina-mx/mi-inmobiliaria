<?php

namespace App\Controllers;

class Calculator extends BaseController
{
    public function index(): string
    {
        return view('calculator/index', [
            'pageTitle' => 'Calculadora de Hipoteca',
            'result'    => null,
        ]);
    }

    public function calculate(): string
    {
        $amount   = (float) $this->request->getPost('amount');
        $months   = (int)   $this->request->getPost('month');
        $interest = (float) $this->request->getPost('interest');

        $result = null;
        if ($amount > 0 && $months > 0 && $interest > 0) {
            $monthlyRate = ($interest / 100) / 12;
            $result = $amount * ($monthlyRate * pow(1 + $monthlyRate, $months))
                             / (pow(1 + $monthlyRate, $months) - 1);
        }

        return view('calculator/index', [
            'pageTitle' => 'Calculadora de Hipoteca',
            'result'    => $result,
            'amount'    => $amount,
            'months'    => $months,
            'interest'  => $interest,
        ]);
    }
}
