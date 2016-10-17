<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Octopuce\Acme\Exception\ApiCallErrorException;
use Octopuce\Acme\Exception\ChallengeFailException;

// -----------------------------------------
// Home, list available domains
$app->get('/', function (Application $app) {
    return $app['twig']->render('index.html.twig', array(
        'domains' => $app['acme.client']['storage']->findAll()
    ));
})
->bind('homepage');


// -----------------------------------------
// Add a new domain to manage
$app->post('/add_domain', function (Application $app, Request $request) {

    $app['acme.client']->newOwnership($request->get('domain'));

    return $app->redirect('/');
})
->bind('add_domain');


// -----------------------------------------
// Get challenge info
$app->get('/challenge/{fqdn}/{type}', function(Application $app, Request $request) {
    return $app->json($app['acme.client']->getChallengeData($request->get('fqdn'), $request->get('type')));
})
->bind('challenge_data');


// -----------------------------------------
// Ask for challenge solving
$app->get('/solve-challenge/{fqdn}/{type}', function (Application $app, Request $request) {

    $output = array();

    try {
        $client = $app['acme.client'];

        $client->challengeOwnership($request->get('fqdn'), $request->get('type'), false);
        $client->signCertificate($request->get('fqdn'));

        $output['msg'] = 'Certificate created and signed successfuly';

    } catch (ApiCallErrorException $e) {
        $output['error'] = $e->getMessage();
    } catch (ChallengeFailException $e) {
        $output['error'] = $e->getMessage();
    }

    return $app->json($output);
})
->bind('challenge_solve');


// -----------------------------------------
// Download certificate
$app->get('/certificate/download/{fqdn}', function (Application $app, Request $request) {

    $certificateContent = $app['acme.client']->getCertificate($request->get('fqdn'));
    $response = new Response($certificateContent, 200, array('Content-Type' => 'application/pkix-cert'));

    $forceDownload = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        sprintf('certificate-%s.der', $request->get('fqdn'))
    );
    $response->headers->set('Content-Disposition', $forceDownload);

    return $response;
})
->bind('certificate_download');


// -----------------------------------------
// Revoke certificate
$app->post('/certificate/revoke/{fqdn}', function (Application $app, Request $request) {

    $app['acme.client']->revokeCertificate($request->get('fqdn'));

    return $app->redirect('/');
})
->bind('certificate_revoke');


// -----------------------------------------
// Renew certificate
$app->post('/certificate/renew/{fqdn}', function (Application $app, Request $request) {

    $app['acme.client']->renewCertificate($request->get('fqdn'));

    return $app->redirect('/');
})
->bind('certificate_renew');


// -----------------------------------------
// Delete domain
$app->post('/delete/{fqdn}', function (Application $app, Request $request) {
    $app['acme.client']['storage']->deleteDomain($request->get('fqdn'));

    return $app->redirect('/');
})
->bind('delete');


// -----------------------------------------
// Error handling
$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
