<?php

namespace App\Controllers;

use App\Libraries\FieldLabels;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /** Igual que Controller::validate(), con etiquetas en español para los mensajes. */
    protected function validate($rules, array $messages = []): bool
    {
        return parent::validate(FieldLabels::apply($rules), $messages);
    }

    /** Igual que Controller::validateData(), con etiquetas en español para los mensajes. */
    protected function validateData(array $data, $rules, array $messages = [], ?string $dbGroup = null): bool
    {
        return parent::validateData($data, FieldLabels::apply($rules), $messages, $dbGroup);
    }
}
