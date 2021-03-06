<?php
namespace Qobo\Duplicates\Test\TestCase\Shell;

use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\ConsoleIntegrationTestCase;
use Qobo\Duplicates\Shell\MapDuplicatesShell;

/**
 * Qobo\Duplicates\Shell\MapDuplicatesShell Test Case
 */
class MapDuplicatesShellTest extends ConsoleIntegrationTestCase
{
    public $fixtures = [
        'plugin.Qobo/Duplicates.Articles',
        'plugin.Qobo/Duplicates.Duplicates',
    ];

    /**
     * ConsoleIo mock
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    public $io;

    /**
     * Test subject
     *
     * @var \Qobo\Duplicates\Shell\MapDuplicatesShell
     */
    public $MapDuplicatesShell;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->io = $this->getMockBuilder('Cake\Console\ConsoleIo')->getMock();
        if ($this->io instanceof ConsoleIo) {
            $this->MapDuplicatesShell = new MapDuplicatesShell($this->io);
        }
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->MapDuplicatesShell);

        parent::tearDown();
    }

    /**
     * Test getOptionParser method
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $this->assertInstanceOf(ConsoleOptionParser::class, $this->MapDuplicatesShell->getOptionParser());
        $this->assertSame('Map Duplicate records', $this->MapDuplicatesShell->getOptionParser()->getDescription());
    }

    /**
     * Test main method
     *
     * @return void
     */
    public function testMain(): void
    {
        $table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');
        $this->MapDuplicatesShell->main();
        $this->assertCount(4, $table->find()->all());
    }
}
