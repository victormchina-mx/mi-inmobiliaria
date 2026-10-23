<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PropertyModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $propertyModel = new PropertyModel();
        $userModel     = new UserModel();

        $data = [
            'pageTitle'    => 'Dashboard',
            'totalUsers'   => $userModel->users()->countAllResults(),
            'totalAgents'  => $userModel->agents()->countAllResults(),
            'totalBuilders'=> $userModel->builders()->countAllResults(),
            'totalProps'   => $propertyModel->countAll(),
            'saleProps'    => $propertyModel->where('stype', 'sale')->countAllResults(),
            'rentProps'    => $propertyModel->where('stype', 'rent')->countAllResults(),
            'apartments'   => $propertyModel->where('type', 'apartment')->countAllResults(),
            'houses'       => $propertyModel->where('type', 'house')->countAllResults(),
            'buildings'    => $propertyModel->where('type', 'building')->countAllResults(),
            'flats'        => $propertyModel->where('type', 'flat')->countAllResults(),
        ];

        return view('admin/dashboard', $data);
    }
}
