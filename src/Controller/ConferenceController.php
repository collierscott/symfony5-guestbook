<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
//        return $this->render('conference/index.html.twig', [
//            'controller_name' => 'ConferenceController',
//        ]);

        return new Response(<<<EOF
            <html>
                <body>
                    <img src="/images/cowboy-putin.jpg"/>
                </body>
            </html>
EOF
        );
    }
}
