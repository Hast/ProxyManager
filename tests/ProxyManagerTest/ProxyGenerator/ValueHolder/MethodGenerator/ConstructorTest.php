<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ProxyManagerTest\ProxyGenerator\ValueHolder\MethodGenerator;

use PHPUnit_Framework_TestCase;
use ProxyManager\ProxyGenerator\ValueHolder\MethodGenerator\Constructor;
use ProxyManagerTestAsset\ClassWithMixedProperties;
use ProxyManagerTestAsset\ClassWithVariadicConstructorArgument;
use ProxyManagerTestAsset\EmptyClass;
use ProxyManagerTestAsset\ProxyGenerator\LazyLoading\MethodGenerator\ClassWithTwoPublicProperties;
use ReflectionClass;
use Zend\Code\Generator\PropertyGenerator;

/**
 * Tests for {@see \ProxyManager\ProxyGenerator\ValueHolder\MethodGenerator\Constructor}
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \ProxyManager\ProxyGenerator\ValueHolder\MethodGenerator\Constructor
 * @group Coverage
 */
class ConstructorTest extends PHPUnit_Framework_TestCase
{
    public function testBodyStructure()
    {
        /* @var $valueHolder PropertyGenerator|\PHPUnit_Framework_MockObject_MockObject */
        $valueHolder = $this->getMock(PropertyGenerator::class);

        $valueHolder->expects($this->any())->method('getName')->will($this->returnValue('foo'));

        $constructor = Constructor::generateMethod(
            new ReflectionClass(
                ClassWithTwoPublicProperties::class
            ),
            $valueHolder
        );

        $this->assertSame('__construct', $constructor->getName());
        $this->assertCount(0, $constructor->getParameters());
        $this->assertSame(
            'static $reflection;

if (! $this->foo) {
    $reflection = $reflection ?: new \ReflectionClass(\'ProxyManagerTestAsset\\\\ProxyGenerator\\\\LazyLoading\\\\'
            . 'MethodGenerator\\\\ClassWithTwoPublicProperties\');
    $this->foo = $reflection->newInstanceWithoutConstructor();
unset($this->bar, $this->baz);

}',
            $constructor->getBody()
        );
    }

    public function testBodyStructureWithoutPublicProperties()
    {
        /* @var $valueHolder PropertyGenerator|\PHPUnit_Framework_MockObject_MockObject */
        $valueHolder = $this->getMock(PropertyGenerator::class);

        $valueHolder->expects($this->any())->method('getName')->will($this->returnValue('foo'));

        $constructor = Constructor::generateMethod(
            new ReflectionClass(EmptyClass::class),
            $valueHolder
        );

        $this->assertSame('__construct', $constructor->getName());
        $this->assertCount(0, $constructor->getParameters());
        $this->assertSame(
            'static $reflection;

if (! $this->foo) {
    $reflection = $reflection ?: new \ReflectionClass(\'ProxyManagerTestAsset\\\\EmptyClass\');
    $this->foo = $reflection->newInstanceWithoutConstructor();
}',
            $constructor->getBody()
        );
    }

    public function testBodyStructureWithStaticProperties()
    {
        /* @var $valueHolder PropertyGenerator|\PHPUnit_Framework_MockObject_MockObject */
        $valueHolder = $this->getMock(PropertyGenerator::class);

        $valueHolder->expects($this->any())->method('getName')->will($this->returnValue('foo'));

        $constructor = Constructor::generateMethod(new ReflectionClass(ClassWithMixedProperties::class), $valueHolder);

        $this->assertSame('__construct', $constructor->getName());
        $this->assertCount(0, $constructor->getParameters());

        $expectedCode = <<<'PHP'
static $reflection;

if (! $this->foo) {
    $reflection = $reflection ?: new \ReflectionClass('ProxyManagerTestAsset\\ClassWithMixedProperties');
    $this->foo = $reflection->newInstanceWithoutConstructor();
unset($this->publicProperty0, $this->publicProperty1, $this->publicProperty2, $this->protectedProperty0, $this->protectedProperty1, $this->protectedProperty2);

\Closure::bind(function (\ProxyManagerTestAsset\ClassWithMixedProperties $this) {
    unset($this->privateProperty0, $this->privateProperty1, $this->privateProperty2);
}, $this, 'ProxyManagerTestAsset\\ClassWithMixedProperties')->__invoke($this);

}
PHP;

        $this->assertSame($expectedCode, $constructor->getBody());
    }

    public function testBodyStructureWithVariadicArguments()
    {
        /* @var $valueHolder PropertyGenerator|\PHPUnit_Framework_MockObject_MockObject */
        $valueHolder = $this->getMock(PropertyGenerator::class);

        $valueHolder->expects($this->any())->method('getName')->will($this->returnValue('foo'));

        $constructor = Constructor::generateMethod(
            new ReflectionClass(ClassWithVariadicConstructorArgument::class),
            $valueHolder
        );

        $this->assertSame('__construct', $constructor->getName());
        $this->assertCount(2, $constructor->getParameters());

        $expectedCode = <<<'PHP'
static $reflection;

if (! $this->foo) {
    $reflection = $reflection ?: new \ReflectionClass('ProxyManagerTestAsset\\ClassWithVariadicConstructorArgument');
    $this->foo = $reflection->newInstanceWithoutConstructor();
\Closure::bind(function (\ProxyManagerTestAsset\ClassWithVariadicConstructorArgument $this) {
    unset($this->foo, $this->bar);
}, $this, 'ProxyManagerTestAsset\\ClassWithVariadicConstructorArgument')->__invoke($this);

}

$this->foo->__construct($foo, ...$bar);
PHP;

        $this->assertSame($expectedCode, $constructor->getBody());
    }
}
