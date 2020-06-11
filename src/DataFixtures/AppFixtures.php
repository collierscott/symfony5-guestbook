<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class AppFixtures extends Fixture
{
    /** @var EncoderFactoryInterface $encoderFactory */
    private $encoderFactory;

    /**
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $one = new Conference();
        $one->setCity('Amsterdam')
            ->setYear('2019')
            ->setIsInternational(true);

        $manager->persist($one);

        $two = new Conference();
        $two->setCity('Dallas')
            ->setYear('2020')
            ->setIsInternational(false);

        $manager->persist($two);

        $com = new Comment();
        $com->setConference($one)
            ->setState('published')
            ->setEmail('scott@onlinespaces.com')
            ->setPhotoFilename('22360694e5a8.jpeg')
            ->setAuthor('Scott')
            ->setText('This is great!');

        $manager->persist($com);

        $com2 = new Comment();
        $com2->setConference($one)
            ->setEmail('scott@onlinespaces.com')
            ->setAuthor('Scott')
            ->setText('This is great!');

        $manager->persist($com2);

        $admin = new Admin();
        $admin->setRoles(['ROLE_ADMIN'])
            ->setUsername('admin')
            ->setPassword($this->encoderFactory->getEncoder(Admin::class)->encodePassword('password', null));
        $manager->persist($admin);

        $manager->flush();
    }
}
