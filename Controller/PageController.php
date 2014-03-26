<?php
namespace Bangpound\Bundle\DrupalBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class PageController extends Controller
{
    public function callbackAction($path = null, $deliver = true)
    {
        menu_set_active_item($path);
        $page_callback_result = menu_execute_active_handler($path, $deliver);
        if (is_int($page_callback_result)) {
            switch ($page_callback_result) {
                case MENU_NOT_FOUND:
                    // Print a 404 page.
                    throw new NotFoundHttpException;
                    break;

                case MENU_ACCESS_DENIED:
                    // Print a 403 page.
                    throw new AccessDeniedHttpException;
                    break;

                case MENU_SITE_OFFLINE:
                    // Print a 503 page.
                    throw new ServiceUnavailableHttpException;
                    break;
            }
        } elseif (!$deliver) {
            $content = drupal_render($page_callback_result);

            return new Response($content);
        } else {
            $response = $this->get('bangpound_drupal.response');
            $response->setContent($page_callback_result);

            return $response;
        }
    }
}
