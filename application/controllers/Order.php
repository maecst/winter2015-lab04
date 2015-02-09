<?php

/**
 * Order handler
 * 
 * Implement the different order handling usecases.
 * 
 * controllers/welcome.php
 *
 * ------------------------------------------------------------------------
 */
class Order extends Application {

    function __construct() {
        parent::__construct();
    }

    // start a new order
    function neworder() {
      
        // increment order number
        $order_num = $this->orders->highest() + 1;        

        // create a new order object and set its properties
        $newOrder = $this->orders->create();
        $newOrder->num = $order_num;
        $newOrder->date = date();
        $newOrder->status = 'a';
        $newOrder->total = 0;
        
        // add to an order
        $this->orders->add($newOrder);
        
        redirect('/order/display_menu/' . $order_num);
    }

    // add to an order
    function display_menu($order_num = null) {
        
        if ($order_num == null) {
            redirect('/order/neworder');
        }

        // get the total for the order
        $total = $this->orders->get($order_num)->total;
        
        // set page info
        $this->data['pagebody'] = 'show_menu';
        $this->data['order_num'] = $order_num;
        $this->data['title'] = 'Order #' . $order_num . ' ( $ ' . $total . ' )';
                
        // Make the columns
        $this->data['meals'] = $this->make_column('m', $order_num);
        $this->data['drinks'] = $this->make_column('d', $order_num);
        $this->data['sweets'] = $this->make_column('s', $order_num);

        $this->render();
    }

    // make a menu ordering column
    function make_column($category, $order_num) {

        $column = $this->menu->some('category', $category);

        foreach($column as $item){
            $item->order_num = $order_num;
        }

        return $column;
    }

    // add an item to an order
    function add($order_num, $item) {

        $this->orders->add_item($order_num, $item);
        
        redirect('/order/display_menu/' . $order_num);
    }

    // checkout order
    function checkout($order_num) {
        
        // set page info
        $this->data['title'] = 'Checking Out';
        $this->data['pagebody'] = 'show_order';
        $this->data['order_num'] = $order_num;
        $this->data['total'] = number_format($this->orders->total($order_num), 2);
        
        // get all the items in an order
        $items = $this->orderitems->group($order_num);
        foreach ($items as $item) {
            $menuItem = $this->menu->get($item->item);
            $item->code = $menuItem->name;
        }
        
        $this->data['items'] = $items;
        
        // check if order is valid
        $valid = $this->orders->validate($order_num);
        
        // if order is not valid, disable the green 'Proceed' button
        if (!$valid) {
            $this->data['okornot'] = "disabled";
        }

        $this->render();
    }

    // proceed with checkout
    function commit($order_num) {

        // if order is not valid, reload display_menu page
        if (!$this->orders->validate($order_num)) {
            redirect('/order/display_menu/' . $order_num);
        }
        
        // otherwise set details for order
        $order = $this->orders->get($order_num);
        $order->date = date(DATE_ATOM);
        $order->status = 'c';
        $order->total = $this->orders->total($order_num);
        $this->orders->update($order);
        
        redirect('/');
    }

    // cancel the order
    function cancel($order_num) {
        
        $this->orders->flush($order_num);
        redirect('/');
    }
}
