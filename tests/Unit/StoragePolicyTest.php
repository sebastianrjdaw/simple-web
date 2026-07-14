<?php
namespace Tests\Unit;
use App\Services\StoragePolicyService; use Tests\TestCase;
class StoragePolicyTest extends TestCase {
 public function test_reserve_has_a_hard_minimum_and_blocks_projected_operations():void{config(['simpleview.storage.reserve_bytes'=>15*1024**3,'simpleview.storage.warning_free_bytes'=>20*1024**3,'simpleview.storage.warning_percent'=>80,'simpleview.storage.block_percent'=>90]);$service=new StoragePolicyService;$normal=$service->evaluate(100*1024**3,50*1024**3);$this->assertSame('ok',$normal['status']);$this->assertTrue($service->operationAllowed(array_merge($normal,['filesystem_free_bytes'=>50*1024**3]),10*1024**3));$critical=$service->evaluate(100*1024**3,14*1024**3);$this->assertSame('critical',$critical['status']);$this->assertFalse($service->operationAllowed(array_merge($critical,['filesystem_free_bytes'=>14*1024**3])));}
}
