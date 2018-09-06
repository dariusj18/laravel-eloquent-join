<?php

namespace Fico7489\Laravel\EloquentJoin\Tests\Tests;

use Fico7489\Laravel\EloquentJoin\Tests\Models\Location;
use Fico7489\Laravel\EloquentJoin\Tests\Models\Order;
use Fico7489\Laravel\EloquentJoin\Tests\Models\OrderItem;
use Fico7489\Laravel\EloquentJoin\Tests\Models\Seller;
use Fico7489\Laravel\EloquentJoin\Tests\Models\User;
use Fico7489\Laravel\EloquentJoin\Tests\TestCase;

class OrderByJoinPivotTest extends TestCase
{
    private function checkOrder($users, $order, $count)
    {
        $this->assertEquals($order[0], $users->get(0)->id);
        $this->assertEquals($order[1], $users->get(1)->id);
        $this->assertEquals($order[2], $users->get(2)->id);
        $this->assertEquals($count, $users->count());
    }

    // public function testWhereJoinOrderByJoinPivot()
    // {
    //     $users = User::whereJoin('orders.id')->orderByJoinPivot('orders.user_type');
    //     $query = $users->getQuery();
    //     return true;
    // }

    public function testOrderByJoinPivot()
    {
        // get orders watched by user 2 and order by weight
        $orders = Order::orderByJoinPivot('users.weight')->get();
        
        $queryTest = 'select "orders".* from "orders" inner join ("order_user" inner join "users" on "users"."id" = "order_user"."user_id" and "users"."deleted_at" is null) on "order_user"."order_id" = "orders"."id" where "orders"."deleted_at" is null order by "order_user"."weight" asc';
        $this->assertEquals($queryTest, $this->fetchQuery());
    }

    public function testOrderByLeftJoinPivot()
    {
        // get orders watched by user 2 and order by weight
        $orders = Order::orderByLeftJoinPivot('users.weight')->get();
        
        $queryTest = 'select "orders".* from "orders" left join ("order_user" inner join "users" on "users"."id" = "order_user"."user_id" and "users"."deleted_at" is null) on "order_user"."order_id" = "orders"."id" where "orders"."deleted_at" is null order by "order_user"."weight" asc';
        $this->assertEquals($queryTest, $this->fetchQuery());
    }

    public function testWhereJoinOrderByJoinPivot()
    {
        // get orders watched by user 2 and order by weight
        $orders = Order::whereJoin('watchers.id', 2)->orderByJoinPivot('watchers.weight')->get();
        
        $queryTest = 'select "orders".* from "orders" inner join ("order_user" inner join "users" on "users"."id" = "order_user"."user_id" and "order_user"."user_type" = ? and "users"."deleted_at" is null) on "order_user"."order_id" = "orders"."id" where "users"."id" = ? and "orders"."deleted_at" is null order by "order_user"."weight" asc';
        $this->assertEquals($queryTest, $this->fetchQuery());
    }

    public function testOrderByJoinPivotWhereJoin()
    {
        // get orders watched by user 2 and order by weight
        $orders = Order::orderByJoinPivot('watchers.weight')->whereJoin('watchers.id', 2)->get();
        
        $queryTest = 'select "orders".* from "orders" inner join ("order_user" inner join "users" on "users"."id" = "order_user"."user_id" and "order_user"."user_type" = ? and "users"."deleted_at" is null) on "order_user"."order_id" = "orders"."id" where "users"."id" = ? and "orders"."deleted_at" is null order by "order_user"."weight" asc';
        $this->assertEquals($queryTest, $this->fetchQuery());
    }
}
