<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cart extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('cart_model');
        $this->load->model('users_model');
        $this->data["dishes"] = null;
        $this->data['total'] = 0;
        $this->data['user'] = null;
        $this->data['hasConfirmed'] = false;
    }

    public function index() {
        if (isset($_SESSION["username"])) {
            $username = $_SESSION["username"];
            $user = $this->db->query("SELECT * FROM user WHERE `username` = '$username'")->row_array();
            $this->data['dishes'] = $this->cart_model->get_dishes_in_cart($_SESSION["username"]);
            $this->data['total'] = $this->total();
            $this->data['user'] = $user;
        }
        $this->load->view('header');
            
        $this->load->view('cart', $this->data);
        
        $this->load->view('footer');
    }

    public function add() {
        $did = $_GET["did"];
        $this->cart_model->add_dishes($did, $_SESSION["username"]);
        $this->index();
    }

    public function remove() {
        if ($this->input->post('qty') !== null && $this->input->post('did') != null) {
            $qty = $this->input->post('qty');
            $did = $this->input->post('did');
            $this->cart_model->remove_dishes($qty, $did, $_SESSION["username"]);
        }

        $this->index();
    }

    public function total() {
        $total = 0.0;
        $dishes = $this->cart_model->get_dishes_in_cart($_SESSION["username"]);
        foreach ($dishes as $dish) :
            $cid = $this->cart_model->get_cid($this->users_model->get_userid($_SESSION["username"]), $dish["did"]);
            $qty = $this->db->query("SELECT qty FROM cart WHERE cid = $cid");
            $total = $total + $dish["price"] * (int)$qty->result();
        endforeach;
        return $total;
    }

    public function qty($userid, $did) {
        $cid = $this->cart_model->get_cid($userid, $did);
        $qty = $this->db->query("SELECT qty FROM cart WHERE cid = $cid;")->result();
        return $qty;
    }

    public function checkout () {
        $address = $_POST["address"];
        $phone = $_POST["phone"];
        // get userid from username
        $username = $_SESSION["username"];
        $userid = $this->users_model->get_userid($username);
        // get dishes id from cart table 
        $dishes = $this->cart_model->get_dishes_in_cart($username);
        foreach ($dishes as $dish) :
            $did = $dish["did"];
            $sql = "INSERT INTO `orders`(`userid`, `did`, `phone`, `address`) 
        VALUES ($userid, $did, $phone, '$address');";
            $this->db->query($sql);
        endforeach;
        $this->data['hasConfirmed'] = true;
        $this->index();
        
    }
}
