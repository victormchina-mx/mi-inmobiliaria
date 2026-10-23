<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FeedbackModel;
use CodeIgniter\HTTP\RedirectResponse;

class Feedback extends BaseController
{
    public function index(): string
    {
        $feedbackModel = new FeedbackModel();

        return view('admin/feedback/index', [
            'pageTitle' => 'Testimonios',
            'feedbacks' => $feedbackModel->withUser()->orderBy('fid', 'DESC')->findAll(),
        ]);
    }

    public function update(int $fid): RedirectResponse
    {
        $feedbackModel = new FeedbackModel();
        $feedbackModel->update($fid, ['status' => (int) $this->request->getPost('status')]);

        return redirect()->to('admin/feedback')->with('success', 'Testimonio actualizado.');
    }

    public function delete(int $fid): RedirectResponse
    {
        $feedbackModel = new FeedbackModel();
        $feedbackModel->delete($fid);

        return redirect()->to('admin/feedback')->with('success', 'Testimonio eliminado.');
    }
}
