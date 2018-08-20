<?php

namespace Fico7489\Laravel\EloquentJoin\Tests\Tests;

use Fico7489\Laravel\EloquentJoin\Tests\Models\Location;
use Fico7489\Laravel\EloquentJoin\Tests\Models\Order;
use Fico7489\Laravel\EloquentJoin\Tests\Models\OrderItem;
use Fico7489\Laravel\EloquentJoin\Tests\Models\Seller;
use Fico7489\Laravel\EloquentJoin\Tests\Models\User;
use Fico7489\Laravel\EloquentJoin\Tests\TestCase;

class OrderByJoinTest extends TestCase
{
    private function checkOrder($users, $order, $count)
    {
        $this->assertEquals($order[0], $users->get(0)->id);
        $this->assertEquals($order[1], $users->get(1)->id);
        $this->assertEquals($order[2], $users->get(2)->id);
        $this->assertEquals($count, $users->count());
    }

    public function testOrderByJoinJoinFirstRelation()
    {
        $users = User::orderByJoinPivot('orders.user_type')->get();
        $this->checkOrder($users, [1, 3, 2], 3);

        $users = User::orderByJoinPivot('orders.user_type', 'desc')->get();
        $this->checkOrder($users, [2, 3, 1], 3);

        $user4 = User::create(['name' => '4']);
        $order2 = Order::find(2);
        $order2->users()->attach($user4, ['user_type' => 'a_cool_dude']);
        $order2_users = User::whereJoin('orders.id', 2)->orderByJoinPivot('orders.user_type')->get();
        $this->checkOrder($order2_users, [4, 3, 2], 3);

        $order2_user3 = Order::find(2)->users()->where('users.id', '=', 3)->first()->pivot;
        $order2_user3['user_type'] = 'zed';
        $order2_user3->save();
        $order2_users = User::whereJoin('orders.id', 2)->orderByJoinPivot('orders.user_type')->get();
        $this->checkOrder($order2_users, [4, 2, 3], 3);
    }
}
