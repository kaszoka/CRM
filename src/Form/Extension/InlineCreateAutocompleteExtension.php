<?php

namespace App\Form\Extension;

use App\Entity\Assortment;
use App\Entity\Industry;
use App\Entity\Source;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class InlineCreateAutocompleteExtension extends AbstractTypeExtension
{
    private const INLINE_CREATABLE_CLASSES = [
        Industry::class,
        Assortment::class,
        Source::class,
        Tag::class,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [CrudAutocompleteType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $class = $options['class'] ?? null;

        if (!\in_array($class, self::INLINE_CREATABLE_CLASSES, true)) {
            return;
        }

        $em = $this->em;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) use ($class, $em): void {
            $data = $event->getData();

            if (!isset($data['autocomplete']) || !\is_array($data['autocomplete'])) {
                return;
            }

            $modified = false;

            foreach ($data['autocomplete'] as $key => $value) {
                if (is_numeric($value)) {
                    continue;
                }

                $name = trim($value);
                if ('' === $name) {
                    unset($data['autocomplete'][$key]);
                    $modified = true;
                    continue;
                }

                $existing = $em->getRepository($class)->findOneBy(['name' => $name]);

                if ($existing) {
                    $data['autocomplete'][$key] = (string) $existing->getId();
                } else {
                    $entity = new $class();
                    $entity->setName($name);
                    $em->persist($entity);
                    $em->flush();
                    $data['autocomplete'][$key] = (string) $entity->getId();
                }

                $modified = true;
            }

            if ($modified) {
                $data['autocomplete'] = array_values($data['autocomplete']);
                $event->setData($data);
            }
        }, 10);
    }
}

