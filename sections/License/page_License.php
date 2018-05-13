<?php

namespace Iris\Config\CRM\sections\License;

use Config;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Iris\Annotation\RequestMethod;
use Iris\Annotation\RequireAuth;

class page_License extends Config
{
    /**
     * @var \IrisSession
     */
    protected $session;

    /**
     * @inheritdoc
     */
    public function __construct($Loader = null, $files = [])
    {
        parent::__construct($Loader, $files);
        $this->session = \IrisSession::getInstance();
    }

    /**
     * @RequireAuth(false)
     * @RequestMethod({"get", "post"})
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    public function request($request) {
        if ($request->getMethod() == 'GET') {
            $data = $this->session->get('flashData');
            $this->session->set('flashData', null);
            return $this->renderView('license/request', $data);
        }

        // Protection from re-sending with F5
        $url = url('page/License/request');
        header(sprintf('Location: %s', $url), true, 303);
        return $this->sendRequest($request);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    protected function sendRequest($request) {
        $data = [
            'messages' => [],
            'errors' => [],
            'status' => 'success',
            'values' => $request->request->all(),
        ];

        $errors = $this->validateLicenseRequest($request);
        if (!empty($errors['messages']))
        {
            $data['messages'] = $errors['messages'];
            $data['status'] = 'danger';
            $data['errors']['fields'] = $errors['fields'];
            $this->session->set('flashData', $data);
            return $this->renderView('license/request', $data);
        }

        $file = CreateLicenseRequest($request);

        if ($request->request->get('reqtype') === 'int') {
            $this->sendWebRequest($file);
            $data['messages'][] = 'Запрос отправлен';
        }
        else {
            $data['messages'][] = 'Запрос сформирован';
            $data['data']['file'] = $file;
        }
        $this->session->set('flashData', $data);
        return $this->renderView('license/request', $data);
    }

    /**
     * @param string $file
     */
    protected function sendWebRequest($file)
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        $fileData = 'data=' . urlencode($file);
        $licenseRequestPage = 'http://license-request.iriscrm.ru/';
        $webRequest = new Request('POST', $licenseRequestPage, $headers, $fileData);
        $response = $client->send($webRequest);
    }

    protected function validateLicenseRequest($request)
    {
        $errors = [
            'messages' => [],
            'fields' => [],
        ];
        if ($request->request->get('login'))
        {
            $errors['messages'][] = 'Сработала защита от СПАМа';
        }

        $required = [
            'email' => 'Ваш e-mail для связи',
            'account' => 'Компания',
            'count' => 'Число одновременно работающих пользователей',
            'allcount' => 'Общее число пользователей',
            'reqtype' => 'Способ отправки запроса',
        ];

        if (!filter_var($request->request->get('email'), FILTER_VALIDATE_EMAIL)) {
            $errors['messages'][] = sprintf('В поле "%s" указан некорректный email адрес', $required['email']);
            $errors['fields']['email']['message'] = sprintf('Укажите корректный email адрес');
        }

        foreach ($required as $requiredFieldCode => $requiredFieldTitle) {
            if (empty($request->request->get($requiredFieldCode))) {
                $errors['messages'][] = sprintf('Заполните поле "%s"', $requiredFieldTitle);
                $errors['fields'][$requiredFieldCode]['message'] = sprintf('Поле обязательно для заполнения');
            }
        }

        return $errors;
    }

}
