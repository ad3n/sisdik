<?php

namespace Fast\SisdikBundle\Tests\Controller;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Client;
use Pixellaneous\UserBundle\Entity\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PembayaranPendaftaranControllerTest extends WebTestCase
{
    public function testCompleteScenario() {
        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie(session_name(), true));

        $crawler = $client->request('GET', '/');

        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository('FastSisdikBundle:User')->findOneByUsername('admintelekomedika');

        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main_firewall', $user->getRoles());
        self::$kernel->getContainer()->get('security.context')->setToken($token);

        $session = $client->getContainer()->get('session');
        $session->set('_security_' . 'main_firewall', serialize($token));
        $session->save();

        $crawler = $client->request('GET', '/payment/registrationfee/812/');

        $this->assertTrue(200 === $client->getResponse()->getStatusCode());

        //$translator = $client->getContainer()->get('translator');

        $form = $crawler->filter('#pay-form')
                ->form(
                        array(
                                'fast_sisdikbundle_pembayaranpendaftarantype[daftarBiayaPendaftaran][0][terpilih]' => 1,
                                'fast_sisdikbundle_pembayaranpendaftarantype[transaksiPembayaranPendaftaran][0][nominalPembayaran]' => '150.000',
                                'fast_sisdikbundle_pembayaranpendaftarantype[transaksiPembayaranPendaftaran][0][keterangan]' => 'lunas formulir',
                        ));

        $client->submit($form);
        $client->submit($form);

        $crawler = $client->followRedirect();

        $this
                ->assertTrue(
                        $crawler->filter('#no-more-tables tr:last-child td:contains("150.000")')->count() > 0);

        /*
        $crawler = $client->followRedirect();

        // Check data in the show view
        $this->assertTrue($crawler->filter('td:contains("Test")')->count() > 0);

        // Edit the entity
        $crawler = $client->click($crawler->selectLink('Edit')->link());

        $form = $crawler->selectButton('Edit')
                ->form(
                        array(
                            'fast_sisdikbundle_pembayaranpendaftarantype[field_name]' => 'Foo',
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
