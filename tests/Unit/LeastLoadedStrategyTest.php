<?php
namespace Tests\Unit;


use App\Domain\LeadDistribution\Strategies\LeastLoadedStrategy;
use App\Domain\ManagerLoad;


test('it distributes leads to least loaded managers', function () {
$strategy = new LeastLoadedStrategy();

$managers = collect([
new ManagerLoad(1, 12),
new ManagerLoad(2, 4),
new ManagerLoad(3, 8),
]);

$strategy->distribute(
$managers,
collect(range(1, 9)),
);

$loads = $managers->mapWithKeys(
fn (ManagerLoad $manager) => [
$manager->managerId => $manager->load,
]
);

expect($loads->get(1))->toBe(12)
->and($loads->get(2))->toBe(11)
->and($loads->get(3))->toBe(10);
});
