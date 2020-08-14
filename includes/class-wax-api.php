<?php

class WaxApi {

	private static $instance;

	private static $server = 'https://api.waxsweden.org';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/*
	 * gets last 100 transactions receiving tokens
	 * */
	public static function get_latest_100_transactions( $account, $server ) {
		$path = '/history/get_actions?limit=1000&filter=eosio.token:transfer&sort=desc&simple=true&account='.$account;
		$args = array(
			'timeout'     => 20,
		);
		$res = wp_remote_get($server.$path, $args);
		$res = rest_ensure_response($res);

		if(empty($res) && empty($res->status) && $res->status !== 200){
			return false;
		}
		$body = json_decode($res->data['body']);

		if(is_object($body) && !empty($body->simple_actions)){
			return $body->simple_actions;
		}
		return false;
	}

	// filter transactions (only wax), and map to only contain execution_trace->action_traces->act->(data)
	// keep the id of the transaction
	public static function transform_transactions($transactions) {
		$mapped = array_map(function ($transaction) {
			return (object) [
				'id' => $transaction->transaction_id,
				'message' => $transaction->data->memo,
				'amount' => $transaction->data->amount,
			  ];
		}, $transactions);
		return $mapped;
	}

	public static function get_latest_transactions($account, $server) {
		$transactions = WaxApi::get_latest_100_transactions($account, $server);
		return WaxApi::transform_transactions($transactions);
	}

}
