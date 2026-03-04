<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PackagesHostDetailsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\PackagesHostDetailsTable Test Case
 */
class PackagesHostDetailsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\PackagesHostDetailsTable
     */
    protected $PackagesHostDetails;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.PackagesHostDetails',
        'app.Hosts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('PackagesHostDetails') ? [] : ['className' => PackagesHostDetailsTable::class];
        $this->PackagesHostDetails = $this->getTableLocator()->get('PackagesHostDetails', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->PackagesHostDetails);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\PackagesHostDetailsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\PackagesHostDetailsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
