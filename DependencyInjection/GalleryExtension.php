<?php

namespace Pixel\GalleryBundle\DependencyInjection;

use Pixel\GalleryBundle\Admin\AlbumAdmin;
use Pixel\GalleryBundle\Entity\Album;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class GalleryExtension extends Extension implements PrependExtensionInterface
{

    use PersistenceExtensionTrait;


    public function prepend(ContainerBuilder $container): void
    {


        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'resources' => [
                        'albums' => [
                            'routes' => [
                                'detail' => 'gallery.get_album',
                                'list' => 'gallery.get_albums',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'album_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => Album::RESOURCE_KEY,
                                'view' => [
                                    'name' => AlbumAdmin::EDIT_FORM_VIEW,
                                    'result_to_view' => [
                                        'id' => 'id',
                                    ],
                                ],
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => Album::LIST_KEY,
                                        'display_properties' => ['title'],
                                        'icon' => 'fa-map',
                                        'label' => 'gallery',
                                        'overlay_title' => 'gallery.list',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_album_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => Album::RESOURCE_KEY,
                                'view' => [
                                    'name' => AlbumAdmin::EDIT_FORM_VIEW,
                                    'result_to_view' => [
                                        'id' => 'id',
                                    ],
                                ],
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => Album::LIST_KEY,
                                        'display_properties' => ['name'],
                                        'icon' => 'fa-map',
                                        'empty_text' => 'gallery.emptyGallery',
                                        'overlay_title' => 'gallery.list',
                                    ],
                                    'auto_complete' => [
                                        'display_property' => 'name',
                                        'search_properties' => ['name'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }
        if ($container->hasExtension('sulu_search')) {
            $container->prependExtensionConfig(
                'sulu_search',
                [
                    'indexes' => [
                        'album' => [
                            'name' => 'gallery.searchName',
                            'icon' => 'fa-images',
                            'view' => [
                                'name' => AlbumAdmin::EDIT_FORM_VIEW,
                                'result_to_view' => [
                                    'id' => 'id',
                                    'locale' => 'locale',
                                ],
                            ],
                            'security_context' => Album::SECURITY_CONTEXT,
                        ],
                    ],
                    'website' => [
                        "indexes" => [
                            "album",
                        ],
                    ],
                ]
            );
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loaderYaml = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loaderYaml->load('services.yaml');
        //$this->configurePersistence($config['objects'], $container);

    }

}

