<?php

/**
 * Data access wrapper for "orders" table.
 *
 * @author jim
 */
class Orders extends MY_Model {

    // constructor
    function __construct() {
        parent::__construct('orders', 'num');
    }

    // add an item to an order
    function add_item($num, $code) {
        
        // get the item
        $item = $this->orderitems->get($num, $code);
        
        // if item is already in order, increment just the quantity
        if ($item) {          
            $item->quantity++;
            $this->orderitems->update($item);
        } 
        // otherwise add item to the order
        else {
            $item = $this->orderitems->create();
            $item->order = $num;
            $item->item = $code;
            $item->quantity = 1;
            $this->orderitems->add($item);
        }

        // update total
        $this->total($num);
    }

    // calculate the total for an order
    function total($num) {
        
        $orderTotal = 0.0;
        $items = $this->orderitems->group($num);
        
        // add price of each item in the order
        foreach ($items as $item) {
            $menuItem = $this->menu->get($item->item);
            $orderTotal += $item->quantity * $menuItem->price;
        }
        
        // update the total
        $order = $this->orders->get($num);
        $order->total = $orderTotal;
        $this->orders->update($order);
        
        return $orderTotal;
    }

    // retrieve the details for an order
    function details($num) {
        
        $items = $this->orderitems->group($num);
        
        foreach ($items as $item) {
            $item->price = $this->menu->get($item->item)->price;
            $item->name = $this->menu->get($item->item)->name;
        }
        
        return $items;
    }

    // cancel an order
    function flush($num) {
        
        $this->orderitems->delete_some($num);
        
        $order = $this->orders->get($num);
        $order->status = 'x';
        $order->total = 0;
        $this->orders->update($order);
    }

    // validate an order
    // it must have at least one item from each category
    function validate($num) {
        
        // get all the items in an order
        $items = $this->orderitems->group($num);
        
        // set flags initially to false
        $meal = false; $drink = false; $sweet = false;
        
        // as long as there is more than one item in the order
        if (count($items) > 0) {
            
            // go through the order and check each item
            foreach ($items as $item) {
                
                $menu = $this->menu->get($item->item);
                $category = $menu->category;
                
                // set flags to true if item of that category was added to order
                switch($category) {
                    case 'm':
                        $meal = true;
                        break;
                    case 'd':
                        $drink = true;
                        break;
                    case 's':
                        $sweet = true;
                        break;
                }
            }
        }
        
        return ($meal && $drink && $sweet);
    }

}
