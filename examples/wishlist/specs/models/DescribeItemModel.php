<?php
require_once(dirname(__FILE__) . '/../SpecHelper.php');

/**
 * Specification for the Item Model.
 *
 *  prøver $this->spec() metode å loade classen med param navnet til spec... hvorfor aner jeg ikke?
 *  Se etter quick-fix i metoden itShouldBeAbleToLoad
 * 
 */
class DescribeItemModel extends KolibriTestCase {
    public $itemName;
    
    /**
     * This method acts as beforeAll() method from PHPSpec
     */
    public function setup () {
        // This method doesnt have to be here if its blank.
    }
    
    /**
     * This method acts as before() method from PHPSpec
     */
    public function preSpec () {
        // This method doesnt have to be here if its blank.
        $item = $this->fixtures['ValidItem'];
        $item->save();
        
        $this->itemName = $item->name;
    }
    
    /**
     * Checks validation for an valid item model.
     */
    public function itShouldBeValid () {
        // QUICK-FIX
        spl_autoload_unregister(array('ClassLoader', 'load'));
        
        $item = $this->fixtures['AnotherItem'];
        $this->spec($item)->should->beValid();
    }
    
    /**
     * Checks validation for an invalid item model.
     */
    public function itShouldBeInvalid () {
        $item = $this->fixtures['InvalidItem'];
        $this->spec($item)->shouldNot->beValid();
    }
    
    /**
     * This spec will try to save an article object
     */
    public function itShouldBeAbleToSave () {
        $item = $this->fixtures['AnotherItem'];
        $saved = $item->save();
        
        $this->spec($saved)->should->beEqualTo(1);
    }
    
    /**
     * This spec will try to load a saved article object
     */
    public function itShouldBeAbleToLoad () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        
        $this->spec($item->name)->should->beEqualTo($this->itemName);
    }

    /**
     * This spec will try to save an invalid article object
     */
    public function itShouldNotBeAbleToSaveAnInvalidItem () {
        $item = $this->fixtures['InvalidItem'];
        $saved = $item->save();
        
        $this->spec($saved)->should->beFalse();
    }
    
    /**
     * This spec will try to delete a saved article object
     */
    public function itShouldBeAbleToDelete () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $item->delete();
        
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $this->spec($item->name)->should->beNull();
    }
    
    /**
     * This method acts as after() method from PHPSpec
     */
    public function postSpec () {
        unset($this->itemName);
    }
    
    /**
     * This method acts as afterAll() method from PHPSpec
     */
    public function tearDown () {
        // This method doesnt have to be here if its blank.
    }
    

}
?>
