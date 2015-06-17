<?php
/**
 * Copyright (C) 2015  Alexander Schmidt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @author     Alexander Schmidt <mail@story75.com>
 * @copyright  Copyright (c) 2015, Alexander Schmidt
 * @date       10.06.2015
 */

namespace AValnar\Doctrine\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\Cache;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AnnotationReaderFactory
{
    private $ignoredNames = [
        'author' => true,
        'api' => true,
        'copyright' => true,
        'date' => true,
        'version' => true,
        'package' => true,
        'method' => true
    ];

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'cache'     => new ApcCache(),
            'debug' => true,
            'indexed' => true
        ));
    }

    /**
     * Return an object with fully injected dependencies
     *
     * @param array $parameters
     * @return Reader
     */
    public function create(array $parameters = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $parameters = $resolver->resolve($parameters);

        $this->registerLoader();
        $this->setIgnoredNames();

        if (!$parameters['cache'] instanceof Cache) {
            throw new \InvalidArgumentException('"cache" has to be instance an instance of ' . Cache::class);
        }

        $reader = new CachedReader(
            new AnnotationReader(),
            $parameters['cache'],
            $parameters['debug']
        );

        if ($parameters['indexed'] === true) {
            return new IndexedReader($reader);
        }

        return $reader;
    }

    private function registerLoader()
    {
        $ignoredNames = $this->ignoredNames;

        AnnotationRegistry::registerLoader(function ($class) use ($ignoredNames) {
            return !isset($ignoredNames[$class]);
        });
    }

    private function setIgnoredNames()
    {
        foreach ($this->ignoredNames as $name => $ignore) {
            if ($ignore === true) {
                AnnotationReader::addGlobalIgnoredName($name);
            }
        }
    }
}