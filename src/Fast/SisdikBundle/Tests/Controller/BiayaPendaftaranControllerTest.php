<?php

namespace Langgas\SisdikBundle\Tests\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BiayaPendaftaranControllerTest extends WebTestCase
{

    public function testCompleteScenario() {
        // Create a new client to browse the application
        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie(session_name(), true));

        // dummy call to bypass the hasPreviousSession check
        $crawler = $client->request('GET', '/');

        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository('LanggasSisdikBundle:User')->findOneByUsername('bendahara');

        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main_firewall', $user->getRoles());
        self::$kernel->getContainer()->get('security.context')->setToken($token);

        $session = $client->getContainer()->get('session');
        $session->set('_security_' . 'main_firewall', serialize($token));
        $session->save();

        $crawler = $client->request('GET', '/fee/registration/');

        $this->assertTrue(200 === $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Tambah Biaya')->link();
        $crawler = $client->click($link);

        // Fill in the form and submit it
        $form = $crawler->selectButton('Tambah')
                ->form(
                        array(
                            'langgas_sisdikbundle_biayapendaftarantype[tahun]' => '11',
                            'langgas_sisdikbundle_biayapendaftarantype[gelombang]' => '1',
                            'langgas_sisdikbundle_biayapendaftarantype[jenisbiaya]' => '11',
                            'langgas_sisdikbundle_biayapendaftarantype[nominal]' => '150.000',
                        // ... other fields to fill
                        ));

        $client->submit($form);
        $crawler = $client->followRedirect();

        // Check data in the show view
        $this->assertTrue($crawler->filter('.page-title:contains("Detail")')->count() > 0);

        /*
        // Edit the entity
        $crawler = $client->click($crawler->selectLink('Edit')->link());

        $form = $crawler->selectButton('Edit')
                ->form(
                        array(
                            'langgas_sisdikbundle_biayapendaftarantype[field_name]' => 'Foo',
                        // ... other fields to fill
                        ));

        $client->submit($form);
        $crawler = $client->followRedirect();

        // Check the element contains an attribute with value equals "Foo"
        $this->assertTrue($crawler->filter('[value="Foo"]')->count() > 0);

        // Delete the entity
        $client->submit($crawler->selectButton('Delete')->form());
        $crawler = $client->followRedirect();

        // Check the entity has been delete on the list
        $this->assertNotRegExp('/Foo/', $client->getResponse()->getContent());
         */
    }

}
