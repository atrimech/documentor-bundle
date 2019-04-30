<?php
/**
 * This file is part of Documentor.
 * Created by MTrimech
 * Date: 4/29/19
 * Time: 11:45 AM
 * @author: Mahdi Trimech Labs <trimechmehdi11@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MTrimech\DocumentorBundle\Generator;


/**
 * Interface GeneratorInterface
 * @package MTrimech\DocumentorBundle\Generator
 */
interface GeneratorInterface
{
    /**
     * Generate Readme File
     *
     * @return mixed
     */
    public function generate();
}