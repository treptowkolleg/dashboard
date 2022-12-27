<?php

namespace Core\Controller;

use App\Entity\SchoolSubjectType;
use App\Repository\GenericRepository;
use Core\Component\ConfigComponent\Config;
use Core\Component\ConfigComponent\RouteConfig;
use Core\Component\HttpComponent\Request;
use Core\Component\HttpComponent\Response;
use Core\Component\SeoComponent\Meta;
use Core\Component\SessionComponent\Session;
use Core\Component\SessionComponent\Translation;
use Core\ErrorHandler\Exception\ResponseException;
use Core\ErrorHandler\ExceptionHandler;
use Core\Model\AbstractModel;
use Core\Model\RepositoryFactory\AbstractRepositoryFactory;
use Exception;
use League\Plates\Engine as View;
use PDO;

abstract class AbstractController implements ControllerInterface
{

    /**
     * @var Meta
     */
    protected Meta $meta;

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Response
     */
    protected Response $response;

    /**
     * @var Session
     */
    protected Session $session;

    /**
     * @var RouteConfig
     */
    private RouteConfig $routes;

    protected Config $trans;

    /**
     * @var array|null
     */
    private ?array $templateData = [];

    /**
     * @var View
     */
    private View $view;

    public function __construct()
    {
        $config = new Config('config/env.yaml');
        $this->session = new Session($config->getConfig('APP_SECRET'));
        $this->session->init();
        $this->routes = new RouteConfig('config/routes.yaml');
        $this->response = new Response($this->routes);
        $this->request = new Request($this->session->get('csrf_check'));



        $trans = new Translation($config, $this->session);
        $this->trans = $trans->parse();

        $this->meta = new Meta($config->getConfig('meta'));
        $this->view = new View(project_root . $config->getConfig('template_base_path'));
        $this->view->addData([
            'response' => $this->response,
            'meta'=> $this->meta,
            'session'=> $this->session,
            'trans' => $this->trans,
            'locale'=> $trans->locale,
            'locales'=> $trans->availableLanguages,
            'flash' => $this->getFlash()
        ]);



    }

    public function generateRandomString($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function denyAccessUnlessGranted(string $role)
    {
        if(false !== $user = $this->session->getUser()){
            if($user->getRole() != $role) {
                $this->response->redirectToRoute(302,'app_index');
            }
        } else {
            $this->response->redirectToRoute(302,'app_index');
        }
    }

    public function denyAccessUnlessHasPermission(string $condition, string $redirectToRoute = 'app_index')
    {
        if(false !== $user = $this->session->getUser()){
            $permissionFound = true;
            foreach($user->getPermissions() as $permission)
            {
                if($condition == $permission->getLabel())
                {
                    $permissionFound = false;

                }
            }

            if($permissionFound)
            {
                $this->response->redirectToRoute(302,$redirectToRoute);
            }
        } else {
            $this->response->redirectToRoute(302,$redirectToRoute);
        }
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    private function addTemplateData(string $key, $value): void
    {
        $this->templateData[$key] = $value;
    }

    /**
     * @return void
     */
    private function generateToken(): void
    {
        $csrfToken = null;
        try {
            $csrfToken = sha1(random_bytes(9));
        } catch (Exception $e) {
        }

        $this->session->set('csrf_token', $csrfToken);
        $this->session->set('csrf_check', $csrfToken);
    }

    /**
     * @param string $route the name of the route defined in config.
     * @param array|null $mandatory the needed route parameters (optional).
     * @param null $anchor the needed anchor (optional).
     * @return string url including protocol, host, request uri and optional anchor.
     */
    public function generateUrlFromRoute(string $route, array $mandatory = null, $anchor = null): string
    {
        try {
            return $this->response->generateUrlFromRoute($route, $mandatory, $anchor);
        } catch (ResponseException $exception)
        {
            $exceptionHandler =  new ExceptionHandler($exception);
            $exceptionHandler->renderView();
            exit;
        }

    }

    /**
     * @param string $entity
     * @return GenericRepository the repository factory to build sql requests.
     */
    public function getRepositoryManager(string $entity): GenericRepository
    {
        return  new GenericRepository($entity);
    }

    /**
     * @param string|null $message
     * @param string|null $type
     */
    public function getFlash(string $message = null, string $type = null): ?string
    {

        $message = $message ? $message : $this->session->get('message');
        $type = $type ? $type : $this->session->get('message_type');
        $type = $type ? $type : 'success';

        if ($message) {

            $flash = $this->render('/app/_toast.html', [
                'type' => $type,
                'message' => $message,
                'trans' => $this->trans,
            ]);
            $this->session->clear('message');
            $this->session->clear('message_type');
            return $flash;
        }
        return null;
    }

    /**
     * @param string $message
     * @param string $type
     * @return void
     */
    public function setFlash(string $message, string $type = 'success'): void
    {
        $this->session->set('message', $message);
        $this->session->set('message_type', $type);
    }


    /**
     * @param string $template path to the template being rendered without ".php"
     * @param array $data data put along the template
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        foreach ($data as $key => $value) {
            $this->addTemplateData($key, $value);
        }
        return $this->view->render($template, $this->templateData);
    }

}
