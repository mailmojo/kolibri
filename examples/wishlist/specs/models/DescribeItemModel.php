<?php
require_once(dirname(__FILE__) . '/../SpecHelper.php');

/**
 * Specification for the Item Model.
 * 
 * To execute something before or after etc, you have helper methods like:
 * <code>setup()</code>, <code>posteSpec()</code>, <code>preSpec()</code>
 * and <code>tearDown()</code> that you can use in your testcase.
 */
class DescribeItemModel extends KolibriContext {
    public $itemName;
    
    /**
     * This method acts as before() method from PHPSpec
     */
    public function setup () {
        // This method doesnt have to be here if its blank.
        $this->itemName = "Toy house";
    }
    
    /**
     * Checks validation for an valid item model.
     */
    public function itShouldValidateWithValidData () {
        $item = $this->fixtures['ValidItem'];
        $this->spec($item)->should->beValid();
    }
    
    /**
     * Checks validation for an invalid item model.
     */
    public function itShouldInvalidateWithInvalidData () {
        $item = $this->fixtures['InvalidItem'];
        $this->spec($item)->shouldNot->beValid();
    }
    
    /**
     * This spec will try to save an item object
     */
    public function itShouldBeAbleToSave () {
        $item = $this->fixtures['ValidItem'];
        $this->spec($item->save())->should->beEqualTo(1);
    }
    
    /**
     * This spec will try to load a saved item object
     */
    public function itShouldBeAbleToLoad () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $this->spec($item->name)->should->beEqualTo($this->itemName);
    }

    /**
     * This spec will try to save an invalid item object
     */
    public function itShouldNotBeAbleToSaveAnInvalidItem () {
        $item = $this->fixtures['InvalidItem'];
        $this->spec($item->save())->should->beEqualTo(0);
    }
    
    /**
     * This spec will try to update an item object
     */
    public function itShouldBeAbleToUpdate () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $item->description = "Test update";
        $this->spec($item->save())->should->beEqualTo(1);
    
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $this->spec($item->description)->should->beEqualTo("Test update");
    }
    
    /**
     * This spec will try to delete a saved item object
     */
    public function itShouldBeAbleToDelete () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $item->delete();
        
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $this->spec($item->name)->should->beEmpty();
    }
    
    /**
     * This method acts as after() method from PHPSpec
     */
    public function tearDown () {
        unset($this->itemName);
    }
    

}
?>
