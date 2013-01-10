// username ok, insert User object
$user = $userManager->createUser();
$user->setIdsiswa($entity);
$user->setIdsekolah($entity->getIdtahunmasuk()->getIdsekolah());
$user->setUsername("{$entity->getIdsekolah()->getId()}.{$entity->getNomorInduk()}");
$user->setName($entity->getNamaLengkap());
$user
      ->setEmail(
              "{$entity->getIdsekolah()->getId()}.{$entity->getNomorInduk()}.{$entity
                      ->getEmail()}");
$user->setPlainPassword(rand(1, 100));
$user
      ->setRoles(
              array(
                  'ROLE_USER', 'ROLE_SISWA'
              ));
$user->setConfirmationToken(null);
$user->setEnabled(false);
$em->persist($user);
$em->flush();
