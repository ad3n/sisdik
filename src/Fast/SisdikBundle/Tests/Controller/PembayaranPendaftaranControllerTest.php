<?php
namespace Fast\SisdikBundle\Tests\Controller;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PembayaranPendaftaranControllerTest extends WebTestCase
{

    public function testCompleteScenario()
    {
        $client = static::createClient();
        $client->getCookieJar()->set(new Cookie(session_name(), true));

        $crawler = $client->request('GET', '/');

        $em = $client->getContainer()
            ->get('doctrine')
            ->getManager();
        $user = $em->getRepository('FastSisdikBundle:User')->findOneByUsername('admintelekomedika');

        $token = new UsernamePasswordToken($user, $user->getPassword(), 'main_firewall', $user->getRoles());
        self::$kernel->getContainer()
            ->get('security.context')
            ->setToken($token);

        $session = $client->getContainer()->get('session');
        $session->set('_security_' . 'main_firewall', serialize($token));
        $session->save();

        $crawler = $client->request('GET', '/payment/registrationfee/813/');

        $this->assertTrue(200 === $client->getResponse()
            ->getStatusCode());

        $firstitem = $crawler->filter('#pay-form .fee-item')
            ->parents()
            ->text();
        $firstitem = str_replace('.', '', trim($firstitem));
        preg_match('/\d+$/', $firstitem, $values);
        $potongan = 50000;
        $jumlahcicilan = 2;
        $jumlahbayar = number_format(($values[0] - $potongan) / $jumlahcicilan, 0, ',', '.');

        $form = $crawler->filter('#pay-form')->form(array(
            'fast_sisdikbundle_pembayaranpendaftarantype[daftarBiayaPendaftaran][0][terpilih]' => 1,
            'fast_sisdikbundle_pembayaranpendaftarantype[transaksiPembayaranPendaftaran][0][nominalPembayaran]' => $jumlahbayar,
            'fast_sisdikbundle_pembayaranpendaftarantype[transaksiPembayaranPendaftaran][0][keterangan]' => 'cicilan pertama',
            'fast_sisdikbundle_pembayaranpendaftarantype[adaPotongan]' => 1,
            'fast_sisdikbundle_pembayaranpendaftarantype[jenisPotongan]' => 'nominal',
            'fast_sisdikbundle_pembayaranpendaftarantype[nominalPotongan]' => $potongan
        ));

        $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter("#no-more-tables tr:last-child td:contains('$jumlahbayar')")
            ->count() > 0);

        // multiple form submit with the same object should raise error
        $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter('.alert-block:contains("alert.registrationfee.is.inserted")')
            ->count() > 0);

        // tes tambah cicilan
        $link = $crawler->filter('#no-more-tables tr:last-child td.row-actions a.icon-shopping-cart')->link();
        $crawler = $client->click($link);
        $keterangan = "cicilan yang kedua";
        $form = $crawler->filter('#pay-form')->form(array(
            'fast_sisdikbundle_pembayaranpendaftarantype[transaksiPembayaranPendaftaran][1][nominalPembayaran]' => $jumlahbayar,
            'fast_sisdikbundle_pembayaranpendaftarantype[transaksiPembayaranPendaftaran][1][keterangan]' => $keterangan
        ));
        $client->submit($form);
        $crawler = $client->followRedirect();
        $this->assertTrue($crawler->filter("dl:contains('$keterangan')")
            ->count() > 0);
    }
}
