<?php
require_once('../TestBootstrap.php');

/**
 * Specification for the Item Model. It contains before and after methods from PHPSpec.
 * TODO: Alot of the spec methods are too large for their purpose. something needs to be
 * done...
 *
 * ellers så prøver $this->spec() metode å loade classen med param navnet til spec... hvorfor aner jeg ikke?
 * 
 */
class DescribeItemModel extends KolibriTestCase {
    private $itemName;
    
    /**
     * This method acts as beforeAll() method from PHPSpec
     */
    public function setup () {
        // This method doesnt have to be here if its blank.
        // echo "this is invoked before every spec method\n";

    }
    
    /**
     * This method acts as before() method from PHPSpec
     */
    public function preSpec () {
        // This method doesnt have to be here if it doesnt contain anything.
        // echo "this is infront of every spec method\n";
        
        $item = $this->fixtures['ValidItem'];
        $item->save();
        
        $this->itemName = $item->name;
    }
    
    /**
     * This spec will try to load a saved article object
     */
    public function itShouldBeAbleToLoadAnArticle () {
        spl_autoload_unregister(array('ClassLoader', 'load'));
        
        $item = Models::init('Item');
        $item->objects->load($this->itemName);

        $this->spec($item->name)->should->beEqualTo($this->itemName);
    }
    
    /**
     * This spec will try to save an article object
     */
    public function itShouldHaveAnItemNameWhenSaved () {
        $item = $this->fixtures['ValidItem'];
        $this->spec($item)->should->beValid();
        $item->save();
        $this->spec($item->name)->shouldNot->beNull();
    }
    
    /**
     * This spec will try to save an invalid article object
     */
    public function itShouldNotBeAbleToSaveAnInvalidArticle () {
        $item = $this->fixtures['InvalidItem'];
        $this->spec($item)->shouldNot->beValid();
        
        try {
            $item->save();
            $this->fail("This Item model is suspose to not be valid but it is.");
        }
        catch(SQLException $e) { }
    }
    
    /**
     * This spec will try to delete a saved article object
     */
    public function itShouldBeAbleToDeleteAnItem () {
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
