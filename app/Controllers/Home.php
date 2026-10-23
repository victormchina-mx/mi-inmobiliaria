<?php

namespace App\Controllers;

use App\Models\FeedbackModel;
use App\Models\PropertyModel;
use App\Models\UserModel;

class Home extends BaseController
{
    public function index(): string
    {
        $propertyModel = new PropertyModel();
        $feedbackModel = new FeedbackModel();
        $userModel     = new UserModel();

        $data = [
            'pageTitle'  => 'Inicio',
            'properties' => $propertyModel->withOwner()->orderBy('created_at', 'DESC')->limit(9)->findAll(),
            'feedback'   => $feedbackModel->active()->findAll(),
            'totalProps' => $propertyModel->countAll(),
            'saleProps'  => $propertyModel->where('stype', 'sale')->countAllResults(),
            'rentProps'  => $propertyModel->where('stype', 'rent')->countAllResults(),
            'totalUsers' => $userModel->countAll(),
            'popularCities' => ['Olisphis', 'Awrerton', 'Floson', 'Ulmore'],
        ];

        return view('home/index', $data);
        //return view('anterior/welcome_message', $data);
    }
}
