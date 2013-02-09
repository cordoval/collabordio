<?php

namespace HT\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use HT\AppBundle\Service\Exception\NotAuthorisedException;
use HT\AppBundle\Form\SearchType;

class DefaultController extends Controller
{
    public function saveTokensToSession($token = null, $tokenSecret = null)
    {
        $session = $this->getRequest()->getSession();
        $session->set('token', $token);
        $session->set('token_secret', $tokenSecret);
    }

    public function loadTokensFromSession()
    {
        $session = $this->getRequest()->getSession();

        return array(
            'oauth_token' => $session->get('token'),
            'oauth_token_secret' => $session->get('token_secret')
        );
    }

    protected function sendRequest($method, $parameters = array())
    {
        $rdio = $this->container->get('ht_app.rdio');
        $rdio->saveTokens($this->loadTokensFromSession());

        try {
            $response = $rdio->sendRequest($method, $parameters);
        } catch (NotAuthorisedException $e) {
            $response = $this->forward('HTAppBundle:Default:authorise');
        }

        return $response;
    }

    /**
    * @Route("/authorise", name="authorise")
    */
    public function authoriseAction()
    {
        $this->saveTokensToSession(null, null);
        $rdio = $this->container->get('ht_app.rdio');
        $redirectUrl = $rdio->requestToken($this->generateUrl('authorise_callback', array(), true));
        $this->saveTokensToSession($rdio->getToken(), $rdio->getTokenSecret());

        return $this->redirect($redirectUrl);
    }

    /**
     * @Route("/authorise-callback", name="authorise_callback")
     */
    public function authoriseCallbackAction(Request $request)
    {
        $rdio = $this->container->get('ht_app.rdio');
        $rdio->saveTokens($this->loadTokensFromSession());
        $rdio->accessToken($request->query->get('oauth_verifier'));
        $this->saveTokensToSession($rdio->getToken(), $rdio->getTokenSecret());

        return $this->redirect($this->generateUrl('test'));
    }

    /**
     * @Route("/search", name="search")
     * @Template
     */
    public function searchAction(Request $request)
    {
        $form = $this->createForm(new SearchType());
        $form->bind($request);
        $query = $form->get('search')->getData();

        $response = $this->sendRequest('search', array('query' => $query, 'types' => 'Track'));

        if (is_array($response)) {
            $tracks = array();  
            foreach ($response['result']['results'] as $result) {
                $tracks[$result['key']] = $result['name'];
            }
            return array('tracks' => $tracks);
        }

        return $response;
    }

    /**
     * @Route("/add/{id}", name="add")
     * @Template
     */
    public function addAction($id)
    {
        $this->
        var_dump($id);

        return $this->createResponse('');
    }
}
