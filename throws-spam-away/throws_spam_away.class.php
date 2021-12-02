<?php

/**
 *
 * <p>ThrowsSpamAway</p> Class
 * WordPress's Plugin
 * @author Takeshi Satoh@GTI Inc. 2021
 * @version 3.2.5
 */
class ThrowsSpamAway {

	const DOMAIN = 'throws-spam-away';

	// データベースのversion
	var $table_name = null;
	// エラータイプ
	var $error_type = null;

	/**
	 * ThrowsSpamAway constructor.
	 */
	public function __construct() {
		global $tsa_spam_tbl_name;
		global $wpdb;
		// language
		load_plugin_textdomain( self::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );
		// Activate
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		// エラー記号
		if ( ! defined( 'MUST_WORD' ) ) {
			define( 'MUST_WORD', 'must_word' );
			define( 'NG_WORD', 'ng_word' );
			define( 'BLOCK_IP', 'block_ip' );
			define( 'SPAM_BLACKLIST', 'spam_champuru' );
			define( 'URL_COUNT_OVER', 'url_count_over' );
			define( 'SPAM_LIMIT_OVER', 'spam_limit_over' );
			define( 'DUMMY_FIELD', 'dummy_param_field' );

			define( 'SPAM_TRACKBACK', 'spam_trackback' );
			define( 'NOT_JAPANESE', 'not_japanese' );

			define( 'NOT_IN_WHITELIST_IP', 'not_in_whitelist_ip' );
		}

		// 接頭辞（wp_）を付けてテーブル名を設定
		$this->table_name = $wpdb->prefix . $tsa_spam_tbl_name;

		// 管理画面メニュー追加
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		global $df_spam_keep_day_cnt;
		global $lower_spam_keep_day_cnt;

		// 保存期間終了したデータ削除
		$skdc = intval( get_option( 'tsa_spam_keep_day_count', $df_spam_keep_day_cnt ) );
		if ( $skdc < $lower_spam_keep_day_cnt ) {
			$skdc = $lower_spam_keep_day_cnt;
		}
		if ( get_option( 'tsa_spam_data_delete_flg', '' ) == '1' ) {
			// 期間 get_option( 'tsa_spam_keep_day_count' ) 日
			$tsearch = $wpdb->get_var( "SELECT 1 FROM " .$this->table_name . " LIMIT 1" );
			if ( $tsearch == 1 ) {
				$del_query = "DELETE FROM " . $this->table_name . " WHERE post_date < %s ";
				$wpdb->query( $wpdb->prepare( $del_query, gmdate( 'Y-m-d 23:59:59', current_time( 'timestamp' ) - ( 86400 * $skdc ) ) ) );
			}
		}
	}

	/**
	 * プラグインインストール後　有効化時処理
	 */
	function activate() {
		global $df_dummy_param_field_flg, $df_on_flg, $df_without_title_str, $df_japanese_string_min_cnt;
		global $df_back_second, $df_caution_msg;
		global $df_caution_msg_pnt, $df_err_msg, $df_url_cnt_chk_flg;
		global $df_ok_url_cnt, $df_url_cnt_over_err_msg, $df_ng_key_err_msg;
		global $df_must_key_err_msg, $df_tb_on_flg, $df_tb_url_flg;
		//global $default_spam_champuru_hosts,
		global $df_spam_champuru_by_text, $df_spam_champuru_flg;
		global $df_ip_block_from_spam_chk_flg, $df_block_ip_address_err_msg, $df_spam_data_save;
		global $df_spam_data_delete_flg, $df_spam_keep_day_cnt;
		global $df_spam_limit_flg, $df_spam_limit_minutes, $df_spam_limit_cnt;
		global $df_spam_limit_over_interval, $df_spam_limit_over_interval_err_msg;
		global $df_only_whitelist_ip_flg;

		// 初期設定値
		update_option( 'tsa_dummy_param_field_flg', $df_dummy_param_field_flg );
		update_option( 'tsa_on_flg', $df_on_flg );
		update_option( 'tsa_without_title_str', $df_without_title_str );
		update_option( 'tsa_japanese_string_min_count', $df_japanese_string_min_cnt );
		update_option( 'tsa_back_second', $df_back_second );
		update_option( 'tsa_caution_msg', $df_caution_msg );
		update_option( 'tsa_caution_msg_point', $df_caution_msg_pnt );
		update_option( 'tsa_error_msg', $df_err_msg );
		update_option( 'tsa_url_count_check_flg', $df_url_cnt_chk_flg );
		update_option( 'tsa_ok_url_count', $df_ok_url_cnt );
		update_option( 'tsa_url_count_over_error_msg', $df_url_cnt_over_err_msg );
		update_option( 'tsa_ng_key_error_message', $df_ng_key_err_msg );
		update_option( 'tsa_must_key_error_message', $df_must_key_err_msg );
		update_option( 'tsa_tb_on_flg', $df_tb_on_flg );
		update_option( 'tsa_tb_url_flg', $df_tb_url_flg );
		delete_option( 'tsa_spam_champuru_hosts' );
		update_option( 'tsa_spam_champuru_by_text', $df_spam_champuru_by_text );
		update_option( 'tsa_spam_champuru_flg', $df_spam_champuru_flg );
		update_option( 'tsa_ip_block_from_spam_chk_flg', $df_ip_block_from_spam_chk_flg );
		update_option( 'tsa_block_ip_address_error_message', $df_block_ip_address_err_msg );
		update_option( 'tsa_spam_data_save', $df_spam_data_save );
		update_option( 'tsa_spam_data_delete_flg', $df_spam_data_delete_flg );
		update_option( 'tsa_spam_keep_day_count', $df_spam_keep_day_cnt );
		update_option( 'tsa_spam_limit_flg', $df_spam_limit_flg );
		update_option( 'tsa_spam_limit_minutes', $df_spam_limit_minutes );
		update_option( 'tsa_spam_limit_count', $df_spam_limit_cnt );
		update_option( 'tsa_spam_limit_over_interval', $df_spam_limit_over_interval );
		update_option( 'tsa_spam_limit_over_interval_error_message', $df_spam_limit_over_interval_err_msg );
		update_option( 'tsa_only_whitelist_ip_flg', $df_only_whitelist_ip_flg );

		// スパムデータベース作成
		$this->tsa_create_tbl();
	}

	/**
	 * プラグイン無効化時処理
	 */
	function deactivate() {
		// アンインストール時に設定値削除
	}

	/**
	 * スパム投稿テーブル作成
	 * $flg がTRUEなら強制的にテーブル作成
	 */
	function tsa_create_tbl() {
		global $wpdb;
		global $tsa_spam_tbl_name;
		global $tsa_db_version;

		$table_name = $wpdb->prefix . $tsa_spam_tbl_name;
		// テーブル作成要フラグ
		$flg = false;
		if ( $wpdb->get_var( "SELECT 1 FROM " .$table_name . " LIMIT 1" ) != 1 ) {
			// テーブルが存在しないため作成する
			$flg = true;
		}

		//DBのバージョン
		//$tsa_db_version
		//現在のDBバージョン取得
		$installed_ver = get_option( 'tsa_meta_version', 0 );
		// DBバージョンが低い　または　テーブルが存在しない場合は作成
		if ( $flg == true || $installed_ver < $tsa_db_version ) {
			// dbDeltaのおかげ様でCREATE文のみ
			$sql = "CREATE TABLE $table_name (
					meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					post_id bigint(20) UNSIGNED DEFAULT '0' NOT NULL,
					ip_address varchar(64),
					post_date timestamp,
					error_type varchar(255),
					author varchar(255),
					comment varchar(255),
					UNIQUE KEY meta_id (meta_id)
					)
					CHARACTER SET 'utf8';";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			//オプションにDBバージョン保存
			update_option( 'tsa_meta_version', $tsa_db_version );
		}
	}

	/**
	 * スパム投稿の記録
	 *
	 * @param string $post_id
	 * @param string $ip_address
	 */
	function save_post_meta( $post_id, $ip_address, $spam_contents ) {
		global $df_spam_data_save;

		if ( get_option( 'tsa_spam_data_save', $df_spam_data_save ) != '1' ) {
			return;
		}

		global $wpdb;

		$error_type = $spam_contents['error_type'];
		$author     = strip_tags( $spam_contents['author'] );
		$comment    = strip_tags( $spam_contents['comment'] );

		//保存するために配列にする
		$set_arr = array(
			'post_id'    => $post_id,
			'ip_address' => $ip_address,
			'error_type' => $error_type,
			'author'     => $author,
			'comment'    => $comment,
		);

		//レコード新規追加
		$wpdb->insert( $this->table_name, $set_arr );
		$wpdb->show_errors();

		return;
	}

	// JS読み込み部
	function tsa_scripts_init() {
		global $post;
		global $tsa_version;

		$comments_open = ( isset( $post->comment_status ) && $post->comment_status != 'closed' );

		// anti-spam の方法を参考に作成しました
		if (
			! is_admin() &&
			! is_home() &&
			! is_front_page() &&
			! is_archive() &&
			! is_search() &&
			$comments_open
		) {
			wp_enqueue_script( 'throws-spam-away-script', plugins_url( '/js/tsa_params.min.js', __FILE__ ), array( 'jquery' ), $tsa_version );
		}
	}

	function comment_form() {
		global $df_caution_msg;
		// 注意文言表示
		$caution_msg = get_option( 'tsa_caution_message', $df_caution_msg );
		// 注意文言が設定されている場合のみ表示する
		if ( strlen( trim( $caution_msg ) ) > 0 ) {
			echo '<p id="throwsSpamAway">' . $caution_msg . '</p>';
		}
	}

	function comment_form_dummy_param_field() {
		global $df_dummy_param_field_flg;
		// 空パラメータフィールド作成
		$dummy_param_field_flg = get_option( 'tsa_dummy_param_field_flg', $df_dummy_param_field_flg );
		if ( $dummy_param_field_flg == '1' ) {
			echo '<p class="tsa_param_field_tsa_" style="display:none;">email confirm<span class="required">*</span><input type="text" name="tsa_email_param_field___" id="tsa_email_param_field___" size="30" value="" />
	</p>';
			echo '<p class="tsa_param_field_tsa_2" style="display:none;">post date<span class="required">*</span><input type="text" name="tsa_param_field_tsa_3" id="tsa_param_field_tsa_3" size="30" value="' . date( 'Y-m-d H:i:s' ) . '" />
	</p>';
		}
	}

	function comment_post( $commentdata ) {
		global $newThrowsSpamAway;
		global $user_ID;
		global $df_back_second;
		global $df_err_msg;
		global $df_ng_key_err_msg;
		global $df_must_key_err_msg;
		global $df_block_ip_address_err_msg;
		global $df_url_cnt_over_err_msg;
		global $df_spam_limit_over_interval_err_msg;
		global $df_only_whitelist_ip_flg;
		global $df_spam_data_save;

		// ログインしている場合は通過させます。
		if ( $user_ID ) {
			return $commentdata;
		}

		// コメント（comment）及び名前（author）の中も検査
		$id      = @$commentdata['comment_post_ID'];
		$author  = @$commentdata['comment_author'];
		$comment = @$commentdata['comment_content'];

		// チェック対象IPアドレス
		$remote_ip = $_SERVER['REMOTE_ADDR'];
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			$ip_array  = explode( ",", $remote_ip );
			$remote_ip = $ip_array[0];
		}

		// 許可リスト優先通過
		// IP制御 任意のIPアドレスをあればブロックする
		$white_ip_addresses    = trim( get_option( 'tsa_white_ip_addresses', '' ) );
		$only_whitelist_ip_flg = get_option( 'tsa_only_whitelist_ip_flg', $df_only_whitelist_ip_flg );
		$white_ip              = ! $newThrowsSpamAway->white_ip_check( $remote_ip );
		// IP制御 許可リストIPのみ許可する場合（許可リストに記入がある場合のみ有効）
		if ( ! empty( $white_ip_addresses ) &&
		     $only_whitelist_ip_flg === '1' && $white_ip == false ) {
			// 許可リスト以外通さない
			$newThrowsSpamAway->error_type = NOT_IN_WHITELIST_IP;
		} else {
			// IP系の検査
			if ( ! $newThrowsSpamAway->ip_check( $remote_ip ) && $white_ip == false ) {
				// アウト！

			} elseif ( $newThrowsSpamAway->validation( $comment, $author, $id ) ) { // コメント検査
				return $commentdata;
			}
		}
		$error_type = $newThrowsSpamAway->error_type;
		$error_msg = get_option( 'tsa_error_message', $df_err_msg );
		switch ( $error_type ) {
			case MUST_WORD :
				$error_msg = get_option( 'tsa_must_key_error_message', $df_must_key_err_msg );
				break;
			case NG_WORD :
				$error_msg = get_option( 'tsa_ng_key_error_message', $df_ng_key_err_msg );
				break;
			case BLOCK_IP :
			case NOT_IN_WHITELIST_IP :
			case SPAM_BLACKLIST :
				$error_msg = get_option( 'tsa_block_ip_address_error_message', $df_block_ip_address_err_msg );
				break;
			case URL_COUNT_OVER :
				$error_msg = get_option( 'tsa_url_count_over_error_message', $df_url_cnt_over_err_msg );
				break;
			case SPAM_LIMIT_OVER :
				$error_msg = get_option( 'tsa_spam_limit_over_interval_error_message', $df_spam_limit_over_interval_err_msg );
				break;
			case DUMMY_FIELD :    // ダミーフィールドの場合は通常メッセージ
			default :
		}
		// 記録する場合はDB記録
		if ( get_option( 'tsa_spam_data_save', $df_spam_data_save ) == '1' ) {
			$spam_contents               = array();
			$spam_contents['error_type'] = $error_type;
			$spam_contents['author']     = mb_strcut( $author, 0, 255 );
			$spam_contents['comment']    = mb_strcut( $comment, 0, 255 );

			$this->save_post_meta( $id, $remote_ip, $spam_contents );
		}
		// 元画面へ戻るタイム計算
		$back_time = ( (int) get_option( 'tsa_back_second', $df_back_second ) ) * 1000;
		// タイム値が０なら元画面へそのままリダイレクト
		if ( $back_time == 0 ) {
			header( 'Location:' . $_SERVER['HTTP_REFERER'] );
			die;
		} else {
			wp_die( $error_msg . '<script type="text/javascript">var closing = function() {location.href="' . $_SERVER['HTTP_REFERER'] . '";}
					window.setTimeout( closing, ' . $back_time . ');</script>' );
		}
	}

	/****** Check Methods *****/
	function validate_comment(
		$author,
		$comment,
		$validate_array
	) {
		global $df_on_flg;    // 日本語以外を弾くかどうか初期値
		global $df_url_cnt_chk_flg;    // URL数を制御するか初期設定値
		global $df_ok_url_cnt;    // 制限する場合のURL数初期設定値
		global $df_japanese_string_min_cnt; // 日本語文字最小含有数
		global $df_without_title_str;  // タイトル文字列は文字列カウントから排除するか　1:する

		//
		$_japanese_string_min_count = get_option( 'tsa_japanese_string_min_count', $df_japanese_string_min_cnt );
		// NGキーワード文字列群
		$_ng_keywords = get_option( 'tsa_ng_keywords', '' );
		// キーワード文字列群　※拒否リストと重複するものは拒否リストのほうが優先です。
		$_must_keywords = get_option( 'tsa_must_keywords', '' );
		// URL数チェック
		$_url_count_check = get_option( 'tsa_url_count_on_flg', $df_url_cnt_chk_flg );
		// 許容URL数設定値
		$_ok_url_count = intval( get_option( 'tsa_ok_url_count', $df_ok_url_cnt ) ); // デフォルト値３（３つまで許容）
		// タイトル文字列を文字列カウントから排除するか デフォルト 1:する
		$tsa_without_title_str = intval( get_option( 'tsa_without_title_str', $df_without_title_str ) );

		$validate_array = array_merge( array(
			'post_id'                       => null,
			'tsa_on_flg'                    => $df_on_flg,
			'tsa_japanese_string_min_count' => $_japanese_string_min_count,
			'tsa_ng_keywords'               => $_ng_keywords,
			'tsa_must_keywords'             => $_must_keywords,
			'tsa_url_count_check'           => $_url_count_check,
			'tsa_ok_url_count'              => $_ok_url_count
		), $validate_array );

		// post->ID
		$post_id = @$validate_array['post_id'];
		// スパムフィルター ON フラグ
		$tsa_on_flg = @$validate_array['tsa_on_flg'];
		// 日本語文字列必須含有数
		$tsa_japanese_string_min_count = @$validate_array['tsa_japanese_string_min_count'];
		$tsa_japanese_string_min_count = intval( $tsa_japanese_string_min_count );
		// NGキーワード文字列群
		$tsa_ng_keywords = @$validate_array['tsa_ng_keywords'];
		// キーワード文字列群　※拒否リストと重複するものは拒否リストのほうが優先です。
		$tsa_must_keywords = @$validate_array['tsa_must_keywords'];
		// URL数チェック
		$tsa_url_count_check = @$validate_array['tsa_url_count_check'];
		// 許容URL数設定値
		$tsa_ok_url_count = @$validate_array['tsa_ok_url_count'];
		$tsa_ok_url_count = intval( $tsa_ok_url_count ); // デフォルト値３（３つまで許容）

		// シングルバイトだけならエラー
		if ( $tsa_on_flg == 1 && $this->is_only_in_singlebyte( $comment ) ) {
			$this->error_type = NOT_JAPANESE;

			return false;
		}

		// マルチバイト文字が含まれている場合は日本語が含まれていればOK
		if ( $tsa_on_flg == 1 ) {
			$count_flg = 0;
			mb_regex_encoding( 'UTF-8' );
			$com_split = $this->mb_str_split( $comment );

			$tit_split = array();

			// タイトル文字列が含まれている場合はそれを除く機能のためタイトル文字列リスト化
			if ( $tsa_without_title_str == 1 && $post_id != null ) {
				global $wpdb;
				$target_post = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->posts . " WHERE ID = %d ", htmlspecialchars( $post_id ) ) );

				$title = $target_post[0]->post_title;

				$tit_split = $this->mb_str_split( $title );

			}
			foreach ( $com_split as $it ) {

				// タイトル文字列を除く
				if ( $tsa_without_title_str == 1 && in_array( $it, $tit_split ) ) {

					// N/A
					// カウントをアップしない（日本語ではない率が上がる）


				} else {

					if ( preg_match( '/[一-龠]+/u', $it ) ) {
						$count_flg += 1;
					}
					if ( preg_match( '/[ァ-ヶー]+/u', $it ) ) {
						$count_flg += 1;
					}
					if ( preg_match( '/[ぁ-ん]+/u', $it ) ) {
						$count_flg += 1;
					}

				}
			}

			$flg = ( $tsa_japanese_string_min_count < $count_flg );
			if ( $flg == false ) {
				$this->error_type = NOT_JAPANESE;

				return false;
			}
		}
		// 日本語文字列チェック抜けたらキーワードチェックを行う
		if ( $tsa_ng_keywords != '' ) {
			$keyword_list = explode( ',', $tsa_ng_keywords );
			foreach ( $keyword_list as $key ) {
				if ( preg_match( '/' . trim( $key ) . '/u', $author . $comment ) ) {
					$this->error_type = NG_WORD;

					return false;
				}
			}
		}
		// キーワードチェック（拒否リスト）を抜けたら必須キーワードチェックを行う
		if ( $tsa_must_keywords != '' ) {
			$keyword_list = explode( ',', $tsa_must_keywords );
			foreach ( $keyword_list as $key ) {
				if ( preg_match( '/' . trim( $key ) . '/u', $author . $comment ) ) {
					// OK
				} else {
					// 必須ワードがなかったためエラー
					$this->error_type = MUST_WORD;

					return false;
				}
			}
		}
		// 含有URL数チェック
		if ( $tsa_url_count_check != '2' ) {
			if ( substr_count( strtolower( $author . $comment ), 'http' ) > $tsa_ok_url_count ) {
				// URL文字列（httpの数）が多いエラー
				$this->error_type = URL_COUNT_OVER;

				return false;
			}
		}

		return true;
	}


	// シングルバイトだけで
	function is_only_in_singlebyte( $comment ) {
		return strlen( bin2hex( $comment ) ) / 2 == mb_strlen( $comment );
	}

	/**
	 * IP制御許可リストチェックメソッド
	 *
	 * @param string $target_ip
	 */
	function white_ip_check( $target_ip ) {
		global $df_only_whitelist_ip_flg;
		$white_ip_addresses    = trim( get_option( 'tsa_white_ip_addresses', '' ) );
		$only_whitelist_ip_flg = get_option( 'tsa_only_whitelist_ip_flg', $df_only_whitelist_ip_flg );

		if ( ! empty( $white_ip_addresses ) ) {
			// 改行区切りの場合はカンマ区切りに文字列置換後リスト化
			$white_ip_addresses = str_replace( "\n", ',', $white_ip_addresses );
			$ip_list            = explode( ',', $white_ip_addresses );
			foreach ( $ip_list as $_ip ) {
				// 指定IPが範囲指定の場合 例：192.168.1.0/24
				if ( strpos( $_ip, '/' ) != false ) {
					if ( $this->in_cidr( $target_ip, $_ip ) ) {
						// 通過対象
						if ( $only_whitelist_ip_flg === '1' ) {
							return false;
						}
					}
				} elseif ( trim( $_ip ) == trim( $target_ip ) ) {
					// 通過対象
					if ( $only_whitelist_ip_flg === '1' ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	/**
	 * IPアドレスのチェックメソッド
	 *
	 * @param string $target_ip
	 */
	function ip_check( $target_ip ) {
		global $wpdb; // WordPress DBアクセス
		global $df_spam_champuru_flg;    // すぱむちゃんぷるー利用初期値

		// スパムフィルター利用あれば始めに通す
		// １．スパムちゃんぷるー
		$spam_filter_spam_champuru_flg = get_option( 'tsa_spam_champuru_flg', $df_spam_champuru_flg );
		if ( $spam_filter_spam_champuru_flg == '1' ) {
			return $this->reject_spam_ip( $target_ip );
		}
		// ２．以降あれば追加

		// IP制御 WordPressのスパムチェックにてスパム扱いしている投稿のIPをブロックするか
		$ip_block_from_spam_chk_flg = get_option( 'tsa_ip_block_from_spam_chk_flg' );

		if ( $ip_block_from_spam_chk_flg === '1' ) {
			// wp_commentsの　comment_approved　カラムが「spam」のIP_ADDRESSからの投稿は無視する
            $comment_spam_select_query = "SELECT DISTINCT comment_author_IP FROM " . $wpdb->comments . " WHERE comment_approved =  'spam' ORDER BY comment_author_IP ASC ";
			$results = $wpdb->get_results( $comment_spam_select_query );
			foreach ( $results as $item ) {
				if ( trim( $item->comment_author_IP ) == trim( $target_ip ) ) {
					// ブロックしたいIP
					$this->error_type = BLOCK_IP;

					return false;
				}
			}
		}
		// IP制御 任意のIPアドレスをあればブロックする
		$block_ip_addresses = trim( get_option( 'tsa_block_ip_addresses', '' ) );
		if ( ! empty( $block_ip_addresses ) ) {
			// 改行区切りの場合はカンマ区切りに文字列置換後リスト化
			$block_ip_addresses = str_replace( "\n", ',', $block_ip_addresses );
			$ip_list            = explode( ',', $block_ip_addresses );
			foreach ( $ip_list as $ip ) {
				// 指定IPが範囲指定の場合 例：192.168.1.0/24
				if ( strpos( $ip, '/' ) != false ) {
					if ( $this->in_cidr( $target_ip, $ip ) ) {
						// ブロックしたいIP
						$this->error_type = BLOCK_IP;

						return false;
					}
				} elseif ( trim( $ip ) == trim( $target_ip ) ) {
					// ブロックしたいIP
					$this->error_type = BLOCK_IP;

					return false;
				}
                // セーフIP
			}
		}

		return true;
	}

	/**
	 * スパムちゃんぷるー代替スパム拒否リスト利用ブロック
	 */
	function reject_spam_ip( $ip ) {

		// スパム拒否リスト																		[tsa_spam_champuru_hosts] 配列型
//		global $default_spam_champuru_hosts;
		// スパム拒否リスト ｂｙ テキスト														[tsa_spam_chmapuru_by_text] 文字列型（カンマ区切り）
		global $df_spam_champuru_by_text;

//		$spam_blacklist_hosts = get_option( 'tsa_spam_champuru_hosts', $default_spam_champuru_hosts );
		$spam_blacklist_by_text = get_option( 'tsa_spam_champuru_by_text', $df_spam_champuru_by_text );

		$spam_blacklist_by_text_lists = explode( ',', $spam_blacklist_by_text );
		if ( count( $spam_blacklist_by_text_lists ) > 0 ) {
			foreach ( $spam_blacklist_by_text_lists as $item ) {
				$item = trim( $item );
			}
		}

		if ( strlen( trim( $spam_blacklist_by_text ) ) == 0 || count( $spam_blacklist_by_text_lists ) == 0 ) {
			return true;
		}

		$check_list = $spam_blacklist_by_text_lists;

		$pattern     = '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/';
		$remote_ip   = $_SERVER['REMOTE_ADDR'];
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			$ip_array  = explode( ",", $remote_ip );
			$remote_ip = $ip_array[0];
		}
		$check_IP = trim( preg_match( $pattern, $ip ) ? $ip : $remote_ip );
		$spam     = false;
		if ( preg_match( $pattern, $check_IP ) ) {

			// check
			$i = 0;
			while ( $i < count( $check_list ) ) {
				$check = implode( '.', array_reverse( preg_split( '\.', $check_IP ) ) ) . '.' . $check_list[ $i ];

				$i ++;

				$result = gethostbyname( $check );

				if ( $result != $check ) {
					$spam = true;
					break;
				}
			}
		}
		if ( $spam ) {
			$this->error_type = SPAM_BLACKLIST;

			return false;
		}

		return true;
	}

	/**
	 * CIDRチェック
	 *
	 * @param string $ip
	 * @param string $cidr
	 *
	 * @return boolean
	 */
	function in_cidr( $ip, $cidr ) {
		list( $network, $mask_bit_len ) = explode( '/', $cidr );
		if ( ! is_nan( $mask_bit_len ) && $mask_bit_len <= 32 ) {
			$host   = 32 - $mask_bit_len;
			$net    = ip2long( $network ) >> $host << $host; // 11000000101010000000000000000000
			$ip_net = ip2long( $ip ) >> $host << $host;    // 11000000101010000000000000000000

			return $net === $ip_net;
		} else {
			// 形式が不正ならば無視するためFALSE
			return false;
		}
	}

	/**
	 * 日本語が含まれているかチェックメソッド
	 *
	 * @param string $comment
	 * @param string $author
	 */
	function validation( $comment, $author, $post_id = null ) {
		global $df_on_flg;    // 日本語以外を弾くかどうか初期値
		global $df_dummy_param_field_flg;    // ダミー項目によるスパム判定初期値
//		global $df_url_cnt_chk_flg;    // URL数を制御するか初期設定値
//		global $df_ok_url_cnt;    // 制限する場合のURL数初期設定値
//		global $df_japanese_string_min_cnt; // 日本語文字最小含有数

		// Throws SPAM Away 起動フラグ  1:起動  2 or Other:オフ
		$tsa_on_flg = get_option( 'tsa_on_flg', $df_on_flg );

		// 一定時間制限チェック
		// 一定時間内スパム認定機能<br />○分以内に○回スパムとなったら○分間、当該IPからのコメントはスパム扱いする設定+スパム情報保存

		// ○分以内に○回スパムとなったら○分間そのIPからのコメントははじくかの設定
		//$default_spam_limit_flg = 2;	// 1:する 2:しない ※スパム情報保存がデフォルトではないのでこちらも基本はしない方向です。
		// ※スパム情報保存していないと機能しません。
		//$default_spam_limit_minutes = 60;		// ６０分（１時間）以内に・・・
		//$default_spam_limit_count = 2;			// ２回までは許そうか。
		//$default_spam_limit_over_interval = 60;	// だがそれを超えたら（デフォルト３回目以降）60分はOKコメントでもスパム扱いするんでよろしく！
		// tsa_spam_limit_flg,tsa_spam_limit_minutes,tsa_spam_limit_count,tsa_spam_limit_over_interval,tsa_spam_limit_over_interval_error_message

		// タイトル文字列は文字列カウントから排除するか　1:する
		global $df_without_title_str;
		$tsa_without_title_str = intval( get_option( 'tsa_without_title_str', $df_without_title_str ) );

		// スパム情報保存フラグ
		$tsa_spam_data_save = get_option( 'tsa_spam_data_save' );
		// 一定時間制限チェック
		$tsa_spam_limit_flg = get_option( 'tsa_spam_limit_flg', '' );
		if ( $tsa_spam_data_save == '1' && $tsa_spam_limit_flg == '1' ) {
			global $df_spam_limit_minutes;
			global $df_spam_limit_over_interval;
			global $df_spam_limit_cnt;
			global $wpdb;
			$tsa_spam_limit_minutes       = intval( get_option( 'tsa_spam_limit_minutes', $df_spam_limit_minutes ) );
			$tsa_spam_limit_over_interval = intval( get_option( 'tsa_spam_limit_over_interval', $df_spam_limit_over_interval ) );
			// ○分以内（インターバルの方が長い場合はインターバル値を利用する）の同一IPからのスパム投稿回数を調べる
			$interval_minutes = ( $tsa_spam_limit_minutes >= $tsa_spam_limit_over_interval ? $tsa_spam_limit_minutes : $tsa_spam_limit_over_interval );

			// 上記が○回を超えているかチェック
			$remote_ip = $_SERVER['REMOTE_ADDR'];
			if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				$ip_array  = explode( ",", $remote_ip );
				$remote_ip = $ip_array[0];
			}

			$ip               = htmlspecialchars( $remote_ip );
			$this_ip_spam_cnt = "
			SELECT ip_address, count(ppd) as spam_count, max(post_date)
			FROM (select ip_address, post_date as ppd, post_date from $this->table_name) as A
			WHERE A.ip_address = '" . $remote_ip . "' AND
					 ppd >= '" . gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 60 * $interval_minutes ) . "'
			GROUP BY ip_address LIMIT 1";
			$query            = $wpdb->get_row( $this_ip_spam_cnt );
			$spam_count       = intval( $query->spam_count );


			// 最後のスパム投稿から○分超えていなければアウト！！
			$tsa_spam_limit_count = intval( get_option( 'tsa_spam_limit_count', $df_spam_limit_cnt ) );
			if ( $spam_count > $tsa_spam_limit_count ) {
				// アウト！
				$this->error_type = SPAM_LIMIT_OVER;

				return false;
			}
		}
		// ダミーフィールド使用する場合、ダミーフィールドに入力値があればエラー
		$tsa_dummy_param_field_flg = get_option( 'tsa_dummy_param_field_flg', $df_dummy_param_field_flg );
		if ( $tsa_dummy_param_field_flg == '1' ) {
			if ( ! empty( $_POST['tsa_param_field_tsa_3'] ) ) { // このフィールドにリクエストパラメータが入る場合はスパム判定
				$this->error_type = DUMMY_FIELD;

				return false;
			}
		}

		// コメントの内容に関するバリデーション
		$result_valid = apply_filters( 'tsa_validate_comment',
			$this->validate_comment(
				$author,
				$comment,
				array(
					'post_id'    => $post_id,
					'tsa_on_flg' => $tsa_on_flg
				)
			), $author, $comment, $post_id, $tsa_on_flg );

		return apply_filters( 'tsa_validate_comment_result', $result_valid );
	}

	function mb_str_split( $string ) {
		return preg_split( '/(?<!^)(?!$)/u', $string );
	}

	/**
	 * Callback admin_menu
	 */
	function admin_menu() {
		$mincap      = 'level_8';
		$spam_mincap = 'level_7';
		if ( function_exists( 'add_menu_page' ) ) {
			add_menu_page( __( 'Throws SPAM Away 設定', self::DOMAIN ), __( 'Throws SPAM Away', self::DOMAIN ), $mincap, 'throws-spam-away', array(
				$this,
				'options_page'
			) );
		}

		if ( function_exists( 'add_submenu_page' ) ) {
			if ( get_option( 'tsa_spam_data_save' ) == '1' ) {
				add_submenu_page( 'throws-spam-away', __( 'スパムデータ', self::DOMAIN ), __( 'スパムデータ', self::DOMAIN ), $spam_mincap, 'throws-spam-away/throws_spam_away.class.php', array(
					$this,
					'spams_list'
				) );
			}
			add_submenu_page( 'throws-spam-away', __( 'スパムコメント一括削除', self::DOMAIN ), __( 'スパムコメント一括削除', 'throws-spam_away' ), $mincap, 'throws-spam-away/throws_spam_away_comments.php' );
		}
		// 従来通りスパムデータ保存しない場合はスルーする
		if ( get_option( 'tsa_spam_data_save' ) != 1 ) {
			// N/A
		} else {
			// プラグインアップデート時もチェックするため常に・・・
			$this->tsa_create_tbl();
		}

	}

	/**
	 * Admin options page
	 */
	function options_page() {
		global $wpdb; // WordPress DBアクセス
		global $tsa_version;

		global $df_on_flg;
		global $df_without_title_str;
		global $df_dummy_param_field_flg;
		global $df_japanese_string_min_cnt;
		global $df_caution_msg;
		global $df_caution_msg_pnt;
		global $df_back_second;
		global $df_err_msg;
		global $df_ng_key_err_msg;
		global $df_must_key_err_msg;
		global $df_tb_on_flg;
		global $df_tb_url_flg;
		global $df_block_ip_address_err_msg;
		global $df_ip_block_from_spam_chk_flg;
		global $df_spam_data_save;
		global $df_url_cnt_over_err_msg;
		global $df_ok_url_cnt;
		global $df_spam_champuru_flg;

		//global $default_spam_champuru_hosts;
		global $df_spam_champuru_by_text;

		global $df_spam_limit_flg;
		global $df_spam_limit_minutes;
		global $df_spam_limit_cnt;
		global $df_spam_limit_over_interval;
		global $df_spam_limit_over_interval_err_msg;

		global $df_spam_data_delete_flg;
		global $df_spam_keep_day_cnt;

		global $lower_spam_keep_day_cnt;

		global $spam_champuru_hosts;

		global $df_only_whitelist_ip_flg;

		// 設定完了の場合はメッセージ表示
		$_saved = false;

		if ( isset( $_POST['tsa_nonce'] ) ) {
			check_admin_referer( 'tsa_action', 'tsa_nonce' );
			update_option( 'tsa_on_flg', intval( $_POST['tsa_on_flg'] ) );
			update_option( 'tsa_japanese_string_min_count', $_POST['tsa_japanese_string_min_count'] );
			update_option( 'tsa_back_second', $_POST['tsa_back_second'] );
			update_option( 'tsa_caution_message', $_POST['tsa_caution_message'] );
			update_option( 'tsa_caution_msg_point', $_POST['tsa_caution_msg_point'] );
			update_option( 'tsa_error_message', $_POST['tsa_error_message'] );
			update_option( 'tsa_ng_keywords', $_POST['tsa_ng_keywords'] );
			update_option( 'tsa_ng_key_error_message', $_POST['tsa_ng_key_error_message'] );
			update_option( 'tsa_must_keywords', $_POST['tsa_must_keywords'] );
			update_option( 'tsa_must_key_error_message', $_POST['tsa_must_key_error_message'] );
			update_option( 'tsa_tb_on_flg', $_POST['tsa_tb_on_flg'] );
			update_option( 'tsa_tb_url_flg', $_POST['tsa_tb_url_flg'] );
			update_option( 'tsa_block_ip_addresses', $_POST['tsa_block_ip_addresses'] );
			update_option( 'tsa_ip_block_from_spam_chk_flg', ( isset( $_POST['tsa_ip_block_from_spam_chk_flg'] ) ? $_POST['tsa_ip_block_from_spam_chk_flg'] : '0' ) );
			update_option( 'tsa_block_ip_address_error_message', $_POST['tsa_block_ip_address_error_message'] );
			update_option( 'tsa_url_count_on_flg', $_POST['tsa_url_count_on_flg'] );
			update_option( 'tsa_ok_url_count', $_POST['tsa_ok_url_count'] );
			update_option( 'tsa_url_count_over_error_message', $_POST['tsa_url_count_over_error_message'] );
			update_option( 'tsa_spam_data_save', ( isset( $_POST['tsa_spam_data_save'] ) ? $_POST['tsa_spam_data_save'] : '0' ) );
			update_option( 'tsa_spam_limit_flg', ( isset( $_POST['tsa_spam_limit_flg'] ) ? $_POST['tsa_spam_limit_flg'] : '0' ) );
			update_option( 'tsa_spam_limit_minutes', $_POST['tsa_spam_limit_minutes'] );
			update_option( 'tsa_spam_limit_count', $_POST['tsa_spam_limit_count'] );
			update_option( 'tsa_spam_limit_over_interval', $_POST['tsa_spam_limit_over_interval'] );
			update_option( 'tsa_spam_limit_over_interval_error_message', $_POST['tsa_spam_limit_over_interval_error_message'] );
			update_option( 'tsa_spam_champuru_flg', ( isset( $_POST['tsa_spam_champuru_flg'] ) ? $_POST['tsa_spam_champuru_flg'] : '0' ) );
			update_option( 'tsa_spam_keep_day_count', $_POST['tsa_spam_keep_day_count'] );
			update_option( 'tsa_spam_data_delete_flg', ( isset( $_POST['tsa_spam_data_delete_flg'] ) ? $_POST['tsa_spam_data_delete_flg'] : '0' ) );
			update_option( 'tsa_white_ip_addresses', $_POST['tsa_white_ip_addresses'] );
			update_option( 'tsa_dummy_param_field_flg', $_POST['tsa_dummy_param_field_flg'] );
			update_option( 'tsa_memo', $_POST['tsa_memo'] );
			update_option( 'tsa_spam_champuru_by_text', $_POST['tsa_spam_champuru_by_text'] );
			update_option( 'tsa_only_whitelist_ip_flg', ( isset( $_POST['tsa_only_whitelist_ip_flg'] ) ? $_POST['tsa_only_whitelist_ip_flg'] : '0' ) );

			// スパムデータベース作成
			$this->tsa_create_tbl();

			$_saved = true;
		}
		wp_enqueue_style( 'thorows-spam-away-styles', plugins_url( '/css/tsa_styles.css', __FILE__ ) );
		?>
        <style>
            table.form-table {

            }

            table.form-table th {
                width: 200px;
            }
        </style>
        <script type="text/Javascript">
            // 配列重複チェック
            var isDuplicate = function (ary, str) {
                for (i = 0; i < ary.length; i++) {
                    if (str == ary[i]) {
                        return true;
                    }
                }
                return false;
            };

            function addIpAddresses(newAddressStr) {
                // チェック用配列
                var test_newAddress_list = newAddressStr.split(",");
                var str = document.getElementById('tsa_block_ip_addresses').value;
                // 現在の配列（テスト用）
                str = str.replace(/\,/g, "\n");
                var test_oldAddress_list = str.split("\n");

                if (str.length > 0) {
                    str += "\n";
                }
                if (newAddressStr.length > 0) {
                    newAddressStr = newAddressStr.replace(/\,/g, "\n");
                }
                str += newAddressStr;
                str = str.replace(/\,/g, "\n");

                var ary = str.split("\n");
                var newAry = new Array;
                var ret = "";

                upd_flg = false;
                upd_ip_str = "";
                for (var i = 0; i < test_newAddress_list.length; i++) {
                    if (!isDuplicate(test_oldAddress_list, test_newAddress_list[i]) && test_newAddress_list[i] != "") {
                        upd_flg = true;
                        upd_ip_str = upd_ip_str + "・" + test_newAddress_list[i] + "\n";
                    }
                }
                if (upd_flg == true) {

                    for (var i = 0; i < ary.length; i++) {
                        if (!isDuplicate(newAry, ary[i]) && ary[i] != "") {
                            newAry.push(ary[i]);
                        }
                    }
                    document.getElementById('tsa_block_ip_addresses').value = newAry.join('\n');
                    alert('新たにIPアドレスを追加しました。\n' + upd_ip_str);
                } else {
                    alert('指定されたIPアドレスは\nすでに追加されています。');
                }
                return false;
            }
        </script>
        <div class="wrap">
            <h2 id="option_setting">Throws SPAM Away設定</h2>
			<?php if ( $_saved ) { ?>
                <div class="updated" style="padding: 10px; width: 50%;" id="message">設定の更新が完了しました。</div>
			<?php } ?>
            <form method="post" action="">
				<?php wp_nonce_field( 'tsa_action', 'tsa_nonce' ) ?>
                <p>
                    <a href="spam_opt">スパム対策機能設定</a> | <a href="#url_opt">URL文字列除外 設定</a> | <a href="#keyword_opt">NGキーワード
                        / 必須キーワード 制御設定</a> | <a href="#tb_opt">トラックバックへの対応設定</a> | <a href="#ip_opt">投稿IPアドレスによる制御設定</a>
                    | <a href="#memo_opt">メモ</a> | <a href="#spam_data_opt">スパムデータベース</a>
                </p>
                <h3 id="spam_opt">スパム対策機能 設定</h3>
				<?php wp_nonce_field( 'update-options' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">人の目には見えないダミーの入力項目を作成し、そこに入力があれば無視対象とする<br>（スパムプログラム投稿に有効です）</th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_dummy_param_field_flg', $df_dummy_param_field_flg ) == '2' ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							}
							?>
                            <label><input type="radio" name="tsa_dummy_param_field_flg"
                                          value="1"<?php esc_attr_e( $chk_1 ); ?> />&nbsp;する</label>&nbsp;
                            <label><input type="radio" name="tsa_dummy_param_field_flg"
                                          value="2"<?php esc_attr_e( $chk_2 ); ?> />&nbsp;しない</label><br>
                            ※ダミー項目の制御にJavaScriptを使用しますのでJavaScriptが動作しない環境からの投稿はスパム判定されてしまいます。ご注意の上、ご利用ください。<br>
                            （初期設定：<?php echo( $df_dummy_param_field_flg == '2' ? "しない" : "する" ); ?>）
                        </td>
                    </tr>
                </table>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">日本語が存在しない場合、無視対象とする<br>（日本語文字列が存在しない場合無視対象となります。）</th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_on_flg', $df_on_flg ) == 2 ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							}
							?> <label><input type="radio" name="tsa_on_flg" value="1" <?php esc_attr_e( $chk_1 ); ?> />&nbsp;する</label>&nbsp;
                            <label><input type="radio" name="tsa_on_flg" value="2" <?php esc_attr_e( $chk_2 ); ?> />&nbsp;しない</label><br>
                            （初期設定：<?php echo( $df_on_flg == '2' ? "しない" : "する" ); ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">タイトルの文字列が含まれる場合、日本語としてカウントしない<br>（日本語を無理やり入れるためにタイトルを利用する方法を排除する）</th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_without_title_str', $df_without_title_str ) != '1' ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							} ?>
                            <label><input type="radio" name="tsa_without_title_str"
                                          value="1"<?php esc_attr_e( $chk_1 ); ?> />&nbsp;する</label>&nbsp;
                            <label><input type="radio" name="tsa_without_title_str"
                                          value="2"<?php esc_attr_e( $chk_2 ); ?> />&nbsp;しない</label><br>
                            （初期設定：<?php echo( $df_without_title_str == '2' ? "しない" : "する" ); ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            日本語文字列含有数<br>
                            （この文字列に達していない場合無視対象となります。）
                        </th>
                        <td>
                            <input type="text" name="tsa_japanese_string_min_count"
                                   value="<?php echo get_option( 'tsa_japanese_string_min_count', $df_japanese_string_min_cnt ); ?>"><br>
                            （初期設定：<?php echo $df_japanese_string_min_cnt; ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">元の記事に戻ってくる時間<br>（秒）※0の場合エラー画面表示しません。
                        </th>
                        <td><input type="text" name="tsa_back_second"
                                   value="<?php echo get_option( 'tsa_back_second', $df_back_second ); ?>"><br>
                            （初期設定：<?php echo $df_back_second; ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" id="tsa_caution_message">コメント欄の下に表示される注意文言</th>
                        <td><input type="text" name="tsa_caution_message" size="80"
                                   value="<?php echo get_option( 'tsa_caution_message', $df_caution_msg ); ?>"><br>
                            （初期設定:<?php echo $df_caution_msg; ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row" id="tsa_caution_msg_point">コメント注意文言の表示位置</th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_caution_msg_point', $df_caution_msg_pnt ) == '2' ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							}
							?> <label><input type="radio"
                                             name="tsa_caution_msg_point" value='1' <?php esc_attr_e( $chk_1 ); ?> />&nbsp;コメント送信ボタンの上</label>&nbsp;
                            <label><input type="radio" name="tsa_caution_msg_point" value="2"
									<?php esc_attr_e( $chk_2 ); ?> />&nbsp;コメント送信フォームの下</label><br>
                            （初期設定：<?php echo( $df_caution_msg_pnt == '2' ? "コメント送信フォームの下" : "コメント送信ボタンの上" ); ?>）
                        </td>
                    </tr>
                </table>
                <p>※表示が崩れる場合、<a href="#tsa_caution_msg_point">「コメント注意文言の表示位置」</a>の変更　や　<a href="#tsa_caution_message">「コメント欄の下に表示される注意文言」</a>を空白にすること　を試してみて下さい。<br>
                    「コメント欄の下に表示される注意文言」が空白の場合は文言表示のタグ自体が挿入されないようになります。</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">日本語文字列規定値未満エラー時に表示される文言<br>（元の記事に戻ってくる時間の間のみ表示）</th>
                        <td><input type="text" name="tsa_error_message" size="80"
                                   value="<?php echo get_option( 'tsa_error_message', $df_err_msg ); ?>"><br>（初期設定:<?php echo $df_err_msg; ?>
                            ）
                        </td>
                    </tr>
                </table>
                <a href="#option_setting" class="alignright"><?php _e( '▲ 上へ', self::DOMAIN ); ?></a>
                <h3 id="url_opt">URL文字列除外 設定</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">URLらしき文字列が混入している場合エラーとするか</th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_url_count_on_flg', '1' ) == '2' ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							}
							?> <label><input type="radio"
                                             name="tsa_url_count_on_flg" value='1' <?php esc_attr_e( $chk_1 ); ?> />&nbsp;する</label>&nbsp;
                            <label><input type="radio" name="tsa_url_count_on_flg" value="2"
									<?php esc_attr_e( $chk_2 ); ?> />&nbsp;しない</label><br> する場合の制限数（入力数値まで許容）：<input
                                    type="text" name="tsa_ok_url_count" size="2"
                                    value="<?php echo get_option( 'tsa_ok_url_count', $df_ok_url_cnt ); ?>"><br>
                            （初期設定: <?php echo $df_ok_url_cnt; ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">URLらしき文字列混入数オーバーエラー時に表示される文言 （元の記事に戻ってくる時間の間のみ表示）</th>
                        <td><input type="text" name="tsa_url_count_over_error_message"
                                   size="80"
                                   value="<?php echo get_option( 'tsa_url_count_over_error_message', $df_url_cnt_over_err_msg ); ?>"><br>
                            （初期設定:<?php echo $df_url_cnt_over_err_msg; ?>）
                        </td>
                    </tr>
                </table>
                <a href="#option_setting" class="alignright"><?php _e( '▲ 上へ', self::DOMAIN ); ?></a>

                <h3 id="keyword_opt">NGキーワード / 必須キーワード 制御設定</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">その他NGキーワード<br>（日本語でも英語（その他）でもNGとしたいキーワードを半角カンマ区切りで複数設定できます。<br>挙動は同じです。NGキーワードだけでも使用できます。）
                        </th>
                        <td><input type="text" name="tsa_ng_keywords" size="80"
                                   value="<?php echo get_option( 'tsa_ng_keywords', '' ); ?>"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">NGキーワードエラー時に表示される文言<br>（元の記事に戻ってくる時間の間のみ表示）
                        </th>
                        <td><input type="text" name="tsa_ng_key_error_message" size="80"
                                   value="<?php echo get_option( 'tsa_ng_key_error_message', $df_ng_key_err_msg ); ?>"><br>
                            （初期設定:<?php echo $df_ng_key_err_msg; ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">その上での必須キーワード<br>（日本語でも英語（その他）でも必須としたいキーワードを半角カンマ区切りで複数設定できます。<br>指定文字列を含まない場合はエラーとなります。※複数の方が厳しくなります。<br>必須キーワードだけでも使用できます。）
                        </th>
                        <td><input type="text" name="tsa_must_keywords" size="80"
                                   value="<?php echo get_option( 'tsa_must_keywords', '' ); ?>"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">必須キーワードエラー時に表示される文言<br>（元の記事に戻ってくる時間の間のみ表示）
                        </th>
                        <td><input type="text" name="tsa_must_key_error_message" size="80"
                                   value="<?php echo get_option( 'tsa_must_key_error_message', $df_must_key_err_msg ); ?>"><br>
                            （初期設定:<?php echo $df_must_key_err_msg; ?>）
                        </td>
                    </tr>
                </table>
                <a href="#option_setting" class="alignright"></a>

                <h3 id="tb_opt">トラックバックへの対応設定</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">上記設定をトラックバック記事にも採用する</th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_tb_on_flg', $df_tb_on_flg ) == "2" ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							}
							?> <label><input type="radio" name="tsa_tb_on_flg"
                                             value='1' <?php esc_attr_e( $chk_1 ); ?> />&nbsp;する</label>&nbsp;
                            <label><input
                                        type="radio" name="tsa_tb_on_flg" value="2" <?php esc_attr_e( $chk_2 ); ?> />&nbsp;しない</label><br>
                            （初期設定：<?php echo( $df_tb_on_flg == '2' ? "しない" : "する" ); ?>）
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">トラックバック記事にも採用する場合、ついでにこちらのURLが含まれているか判断する
                        </th>
                        <td><?php
							$chk_1 = '';
							$chk_2 = '';
							if ( get_option( 'tsa_tb_url_flg', $df_tb_url_flg ) == '2' ) {
								$chk_2 = ' checked="checked"';
							} else {
								$chk_1 = ' checked="checked"';
							}
							?> <label><input type="radio" name="tsa_tb_url_flg"
                                             value='1' <?php esc_attr_e( $chk_1 ); ?> />&nbsp;する</label>&nbsp;
                            <label><input
                                        type="radio" name="tsa_tb_url_flg" value="2" <?php esc_attr_e( $chk_2 ); ?> />&nbsp;しない</label><br>
                            （初期設定：<?php echo( $df_tb_url_flg == '2' ? "しない" : "する" ); ?>）
                        </td>
                    </tr>
                </table>
                <a href="#option_setting" class="alignright"><?php _e( '▲ 上へ', self::DOMAIN ); ?></a>

                <h3 id="ip_opt">投稿IPアドレスによる制御設定</h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row" rowspan="2">SPAM拒否リスト利用</th>
                        <td><?php
							$chk = '';
							if ( get_option( 'tsa_spam_champuru_flg', $df_spam_champuru_flg ) == '1' ) {
								$chk = ' checked="checked"';
							}
							?>
                            <label><input type="checkbox" name="tsa_spam_champuru_flg"
                                          value='1' <?php esc_attr_e( $chk ); ?> />スパム拒否リストサービスに登録されているIPアドレスからのコメントを拒否する</label><br>
                            （初期設定：<?php echo( $df_spam_champuru_flg == '2' ? "しない" : "する" ); ?>）
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <h4>※多ければ多いほどトラッフィク量が上がりますので注意してください。</h4>

                            <strong>【利用するスパム拒否リストサービスをテキスト入力（カンマ区切り）】</strong><br>
                            <input type="text" name="tsa_spam_champuru_by_text"
                                   size="80"
                                   value="<?php echo get_option( 'tsa_spam_champuru_by_text', $df_spam_champuru_by_text ); ?>"><br>（初期設定：<?php echo $df_spam_champuru_by_text; ?>
                            ）

                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">WordPressのコメントで「スパム」にしたIPからの投稿にも採用する</th>
                        <td><?php
							$chk = '';
							if ( get_option( 'tsa_ip_block_from_spam_chk_flg', $df_ip_block_from_spam_chk_flg ) == '1' ) {
								$chk = ' checked="checked"';
							}
							?> <label><input type="checkbox"
                                             name="tsa_ip_block_from_spam_chk_flg" value='1'
									<?php esc_attr_e( $chk ); ?> />&nbsp;スパム投稿設定したIPアドレスからの投稿も無視する</label>&nbsp;※Akismet等で自動的にスパムマークされたものも含む<br>
                            （初期設定：<?php echo( $df_ip_block_from_spam_chk_flg != '1' ? "しない" : "する" ); ?>）<br>
							<?php
							// wp_commentsの　comment_approved　カラムが「spam」のIP_ADDRESSからの投稿は無視する
							$results = $wpdb->get_results( "SELECT DISTINCT comment_author_IP FROM  $wpdb->comments WHERE comment_approved =  'spam' ORDER BY comment_author_IP ASC " );
							?>現在「spam」フラグが付いているIPアドレス：<br>
                            <blockquote>
								<?php
								$add_ip_addresses = '';
								foreach ( $results as $item ) {
									$spam_ip = esc_attr( $item->comment_author_IP );
									// ブロックしたいIP
									if ( strlen( $add_ip_addresses ) > 0 ) {
										$add_ip_addresses .= ',';
									}
									$add_ip_addresses .= $spam_ip;
									?>
                                    <b><?php esc_attr_e( $spam_ip ); ?> </b><br>
									<?php
								}
								?>
                                &nbsp;<input type="button"
                                             onclick="javascript:addIpAddresses('<?php echo $add_ip_addresses; ?>');"
                                             value="これらのIPアドレスを任意のブロック対象IPアドレスにコピーする"><br>
                            </blockquote>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">任意のIPアドレスからの投稿も無視したい場合、対象となるIPアドレスを記述してください。<br>改行区切りで複数設定できます。（半角数字とスラッシュ、ドットのみ）<br>※カンマは自動的に改行に変換されます
                        </th>
                        <td><textarea name="tsa_block_ip_addresses"
                                      id="tsa_block_ip_addresses" cols="80"
                                      rows="10"><?php echo get_option( 'tsa_block_ip_addresses', '' ); ?></textarea>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">ブロック対象のIPアドレスからの投稿時に表示される文言<br>（元の記事に戻ってくる時間の間のみ表示）
                        </th>
                        <td><input type="text" name="tsa_block_ip_address_error_message"
                                   size="80"
                                   value="<?php echo get_option( 'tsa_block_ip_address_error_message', $df_block_ip_address_err_msg ); ?>"><br>（初期設定：<?php echo $df_block_ip_address_err_msg; ?>
                            ）
                        </td>
                    </tr>
                </table>
                <p>※上記のスパムチェックから除外するIPアドレスがあれば下記に設定してください。優先的に通過させます。<br>※トラックバックは優先通過ではありません。</p>
                <table class="form-table">
                    <tr style="background-color: #fefefe;" valign="top">
                        <th scope="row"><strong>IP制御免除<br>許可リスト</strong></th>
                        <td>
							<?php _e( '※ここに登録したIPアドレスはスパムフィルタを掛けず優先的に通します。<br>※日本語以外の言語でご利用になられるお客様のIPアドレスを登録するなどご利用ください。<br>改行区切りで複数設定できます。範囲指定も可能です。（半角数字とスラッシュ、ドットのみ）', self::DOMAIN ); ?>
                            <textarea name="tsa_white_ip_addresses"
                                      id="tsa_white_ip_addresses" cols="80"
                                      rows="10"><?php echo get_option( 'tsa_white_ip_addresses', '' ); ?></textarea><br>
							<?php
							$chk = '';
							if ( get_option( 'tsa_only_whitelist_ip_flg', $df_only_whitelist_ip_flg ) == '1' ) {
								$chk = ' checked="checked"';
							}
							?> <label><input type="checkbox"
                                             name="tsa_only_whitelist_ip_flg" value='1'
									<?php esc_attr_e( $chk ); ?> />&nbsp;<?php _e( '許可リストに登録したIPアドレス以外からの投稿を無視する（許可リストへの登録がない場合は有効になりません）', self::DOMAIN ); ?>
                            </label><br>
							<?php _e( '（初期設定：', self::DOMAIN ); ?><?php echo( $df_only_whitelist_ip_flg != '1' ? __( "しない", self::DOMAIN ) : __( "する", self::DOMAIN ) ); ?><?php _e( '）', self::DOMAIN ); ?>
                            <br>
							<?php _e( '※許可リストで登録したIP以外の投稿は無視されますのでこの設定は慎重に行ってください。（すべての設定より優先します）<br>※エラーメッセージは「ブロック対象のIPアドレスからの投稿時に表示される文言」が使われます。（エラー表示時のみ）', self::DOMAIN ); ?>
                        </td>
                    </tr>

                </table>
                <a href="#option_setting" class="alignright"><?php _e( '▲ 上へ', self::DOMAIN ); ?></a>

                <h3 id="memo_opt"><?php _e( 'メモ（スパム対策情報や IPアドレス・NGワードその他メモ備忘録としてご自由にお使い下さい）', self::DOMAIN ); ?></h3>
                <p><?php _e( 'この欄の内容が表示されることはありません。', self::DOMAIN ); ?></p>
                <table class="form-table">
                    <tr valign="top">
                        <td>
                            <textarea name="tsa_memo" style="width: 80%;"
                                      rows="10"><?php echo get_option( 'tsa_memo', '' ); ?></textarea>

                        </td>
                    </tr>
                </table>
                <a href="#option_setting" class="alignright"><?php _e( '▲ 上へ', self::DOMAIN ); ?></a>

                <h3 id="spam_data_opt"><?php _e( 'スパムデータベース', self::DOMAIN ); ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'スパムコメント投稿情報を保存しますか？', self::DOMAIN ); ?></th>
                        <td><?php
							$chk = '';
							if ( get_option( 'tsa_spam_data_save', $df_spam_data_save ) == '1' ) {
								$chk = ' checked="checked"';
							}
							?> <label><input type="checkbox"
                                             name="tsa_spam_data_save"
                                             value='1' <?php esc_attr_e( $chk ); ?> />&nbsp;<?php _e( 'スパムコメント情報を保存する', self::DOMAIN ); ?>
                            </label><br>
							<?php echo sprintf( __( "※Throws SPAM Away設定画面表示時に時間がかかることがあります。<br>※「保存する」を解除した場合でもテーブルは残りますので%d日以内の取得データは表示されます。", self::DOMAIN ), get_option( 'tsa_spam_keep_day_count', $df_spam_keep_day_cnt ) ); ?>
                            <br>
							<?php echo sprintf( __( "（初期設定：%s）", self::DOMAIN ), ( $df_spam_data_save != '1' ? __( "しない", self::DOMAIN ) : __( "する", self::DOMAIN ) ) ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'スパムデータを表示する期間', self::DOMAIN ); ?></th>
                        <td>
                            <input
                                    type="text" name="tsa_spam_keep_day_count" size="3"
                                    value="<?php echo get_option( 'tsa_spam_keep_day_count', $df_spam_keep_day_cnt ); ?>"><?php echo sprintf( __( "日分（最低%d日）", self::DOMAIN ), $lower_spam_keep_day_cnt ); ?><?php echo sprintf( __( "（初期設定： %d）", self::DOMAIN ), $df_spam_keep_day_cnt ); ?>
                            <br>&nbsp;
							<?php
							$chk = '';
							if ( get_option( 'tsa_spam_data_delete_flg', $df_spam_data_delete_flg ) == '1' ) {
								$chk = ' checked="checked"';
							}
							?>
                            <label><input type="checkbox" name="tsa_spam_data_delete_flg" value='1'
									<?php esc_attr_e( $chk ); ?> >&nbsp;<?php _e( "期間が過ぎたデータを削除する", self::DOMAIN ); ?>
                            </label><br>
							<?php echo sprintf( __( "※一度消したデータは復活出来ませんのでご注意ください。また最低%d日分は保存されます。", self::DOMAIN ), $lower_spam_keep_day_cnt ); ?>
                        </td>
                    </tr>
                </table>
                <p><?php _e( "一定時間内スパム認定機能<br>○分以内に○回スパムとなったら○分間、当該IPからのコメントはスパム扱いする設定<br><b>※一定時間以内にスパム投稿された回数を測定していますので「スパムコメント情報を保存する」機能がオフの場合は機能しません。</b>", self::DOMAIN ); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( "機能設定", self::DOMAIN ); ?></th>
                        <td><?php
							$chk = '';
							if ( get_option( 'tsa_spam_limit_flg', $df_spam_limit_flg ) == '1' ) {
								$chk = ' checked="checked"';
							}
							?> <label><input type="checkbox" name="tsa_spam_limit_flg" value='1'
									<?php esc_attr_e( $chk ); ?> />&nbsp;<?php _e( "機能させる", self::DOMAIN ); ?></label>
							<?php echo sprintf( __( "（初期設定： %s）", self::DOMAIN ), ( $df_spam_limit_flg != '1' ? __( "しない", self::DOMAIN ) : __( "する", self::DOMAIN ) ) ); ?>
                            <br>
							<?php _e( "一定時間:", self::DOMAIN ); ?><input
                                    type="text" name="tsa_spam_limit_minutes" size="3"
                                    value="<?php echo get_option( 'tsa_spam_limit_minutes', $df_spam_limit_minutes ); ?>"><?php _e( "分以内に", self:: DOMAIN ); ?>
							<?php _e( "一定回数:", self::DOMAIN ); ?><input type="text" name="tsa_spam_limit_count" size="3"
                                                                        value="<?php echo get_option( 'tsa_spam_limit_count', $df_spam_limit_cnt ); ?>"><?php _e( "回スパムとなったら", self::DOMAIN ); ?>
							<?php _e( "<b>次から</b>", self::DOMAIN ); ?>
							<?php _e( "一定時間:", self::DOMAIN ); ?><input type="text" name="tsa_spam_limit_over_interval"
                                                                        size="3"
                                                                        value="<?php echo get_option( 'tsa_spam_limit_over_interval', $df_spam_limit_over_interval ); ?>"><?php _e( "分間", self::DOMAIN ); ?>
                            <br>
							<?php echo sprintf(
								__( "（初期設定：一定時間「%d」分以内に一定回数「%d」回スパムとなったら次から「%d」分間）<br>当該IPアドレスからのコメントを強制スパム扱いします。", self::DOMAIN ),
								$df_spam_limit_minutes, $df_spam_limit_cnt, $df_spam_limit_over_interval ); ?>
                            <br>
							<?php _e( "エラーメッセージは：", self::DOMAIN ); ?><input type="text"
                                                                             name="tsa_spam_limit_over_interval_error_message"
                                                                             size="80"
                                                                             value="<?php echo get_option( 'tsa_spam_limit_over_interval_error_message', $df_spam_limit_over_interval_err_msg ); ?>"><br>
							<?php echo sprintf( __( "（初期設定：%s）", self::DOMAIN ), $df_spam_limit_over_interval_err_msg ); ?>
                        </td>
                    </tr>
                </table>
                <a href="#option_setting" class="alignright"><?php _e( '▲ 上へ', self::DOMAIN ); ?></a>

                <input type="hidden" name="action" value="update">
				<?php /**
				 * <input
				 * type="hidden" name="page_options"
				 * value="tsa_on_flg,tsa_japanese_string_min_count,tsa_back_second,tsa_caution_message,tsa_caution_msg_point,tsa_error_message,tsa_ng_keywords,tsa_ng_key_error_message,tsa_must_keywords,tsa_must_key_error_message,tsa_tb_on_flg,tsa_tb_url_flg,tsa_block_ip_addresses,tsa_ip_block_from_spam_chk_flg,tsa_block_ip_address_error_message,tsa_url_count_on_flg,tsa_ok_url_count,tsa_url_count_over_error_message,tsa_spam_data_save,tsa_spam_limit_flg,tsa_spam_limit_minutes,tsa_spam_limit_count,tsa_spam_limit_over_interval,tsa_spam_limit_over_interval_error_message,tsa_spam_champuru_flg,tsa_spam_keep_day_count,tsa_spam_data_delete_flg,tsa_white_ip_addresses,tsa_dummy_param_field_flg,tsa_memo,tsa_spam_champuru_by_text,tsa_only_whitelist_ip_flg" />
				 */ ?>
                <p class="submit" id="tsa_submit_button">
                    <input type="submit" class="button-primary"
                           value="<?php _e( 'Save Changes' ) ?>">
                </p>
                <p>Throws SPAM Away version <?php echo $tsa_version; ?></p>

            </form>
        </div>
		<?php
	}

	/**
	 * トラックバック用メソッド
	 *
	 * @param $tb
	 *
	 * @return mixed
	 */
	function trackback_spam_away( $tb ) {
		global $newThrowsSpamAway;
		global $df_spam_data_save;

		$tsa_tb_on_flg  = get_option( 'tsa_tb_on_flg' );
		$tsa_tb_url_flg = get_option( 'tsa_tb_url_flg' );
		$siteurl        = get_option( 'siteurl' );
		// トラックバック OR ピンバック時にフィルタ発動
		if ( $tsa_tb_on_flg == "2" || ( $tb['comment_type'] != 'trackback' && $tb['comment_type'] != 'pingback' ) ) {
			return $tb;
		}

		// SPAMかどうかフラグ
		$tb_val['is_spam'] = false;

		// コメント判定
		$author  = $tb["comment_author"];
		$comment = $tb["comment_content"];
		$post_id = $tb["comment_post_ID"];
		// IP系の検査
		$remote_ip = $_SERVER['REMOTE_ADDR'];
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			$ip_array  = explode( ",", $remote_ip );
			$remote_ip = $ip_array[0];
		}

		if ( ! $newThrowsSpamAway->ip_check( $remote_ip ) ) {
			$tb_val['is_spam'] = true;
		} elseif ( ! $newThrowsSpamAway->validation( $comment, $author, $post_id ) ) {  // 検査します！
			$tb_val['is_spam'] = true;
		} elseif ( $tsa_tb_url_flg == '1' && stripos( $comment, $siteurl ) == false ) { // URL検索する場合、URL包含検査 （このブログのURLを含んでない場合エラー
			$tb_val['is_spam'] = true;    // スパム扱い
		}
		// トラックバックスパムがなければ返却・あったら捨てちゃう
		if ( ! $tb_val['is_spam'] ) {
			// トラックバック内に日本語存在（または禁止語句混入なし）
			return $tb;
		} else {
			if ( get_option( 'tsa_spam_data_save', $df_spam_data_save ) == '1' ) {
				$spam_contents               = array();
				$spam_contents['error_type'] = SPAM_TRACKBACK;
				$spam_contents['author']     = mb_strcut( strip_tags( $author ), 0, 255 );
				$spam_contents['comment']    = mb_strcut( strip_tags( $comment ), 0, 255 );

				$this->save_post_meta( $post_id, $remote_ip, $spam_contents );
			}
			die( 'Your Trackback Throws Away.' );
		}
	}

	/**
	 * 当該IPアドレスからの最終投稿日時取得
	 *
	 * @param string ip_address
	 *
	 * @return 最終投稿日時 Y-m-d H:i:s
	 */
	function get_last_spam_comment( $ip_address = null ) {
		global $wpdb;
		// IPアドレスがなければNULL返却
		if ( $ip_address == null ) {
			return null;
		}
		// 最終コメント情報取得
		$qry_str = "SELECT A.post_date, A.post_id, B.error_type, B.author as spam_author, B.comment as spam_comment FROM  $this->table_name as A
					INNER JOIN $this->table_name as B ON A.ip_address = B.ip_address AND A.post_date = B.post_date
					WHERE A.ip_address = '" . htmlspecialchars( $ip_address ) . "' ORDER BY A.post_date DESC LIMIT 1 ";
		$results = $wpdb->get_results( $qry_str );
		if ( count( $results ) > 0 ) {
			return $results[0];
		}

		return null;
	}

	/**
	 * スパムデータベース表示
	 *
	 * @param
	 *
	 * @return HTML
	 */
	function spams_list() {
		global $wpdb;
		global $lower_spam_keep_day_cnt, $df_spam_keep_day_cnt;
		$_saved = false;

		// ブラックIPリスト
		$block_ip_addresses_str = get_option( 'tsa_block_ip_addresses', '' );
		$block_ip_addresses     = str_replace( "\n", ",", $block_ip_addresses_str );
		$ip_list                = explode( ",", $block_ip_addresses );

		$act = ( isset( $_POST['act'] ) ? esc_attr( $_POST['act'] ) : null );

		if ( isset( $_POST['tsa_nonce'] ) ) {
			check_admin_referer( 'tsa_action', 'tsa_nonce' );

			// スパム情報から 特定IPアドレス削除
			if ( $act == "remove_ip" ) {
				$remove_ip_address = @htmlspecialchars( $_POST['ip_address'] );
				if ( ! isset( $remove_ip_address ) || strlen( $remove_ip_address ) == 0 ) {
					// N/A
				} else {
					// スパムデータベースから特定IP情報削除
					$wpdb->query(
						"DELETE FROM " . $this->table_name . " WHERE ip_address = '" . $remove_ip_address . "' "
					);
					$_saved  = true;
					$message = sprintf( __( "スパムデータから %s のデータを削除しました。", self::DOMAIN ), $remove_ip_address );
				}
			} elseif ( $act == "add_ip" ) {
				$add_ip_address = @htmlspecialchars( $_POST['ip_address'] );
				if ( ! isset( $add_ip_address ) || strlen( $add_ip_address ) == 0 ) {
					// N/A
				} else {
					// 対象IPアドレスに一つ追加
					$dup_flg = false;
					foreach ( $ip_list as $ip ) {
						if ( $ip == trim( $add_ip_address ) ) {
							$_saved  = true;
							$message = sprintf( __( "%s はすでに設定されています。", self::DOMAIN ), $add_ip_address );
							$dup_flg = true;
							break;
						}
					}
					if ( $dup_flg == false ) {
						$added_block_ip_addresses_str = $block_ip_addresses_str . "\n" . $add_ip_address;
						update_option( "tsa_block_ip_addresses", $added_block_ip_addresses_str );
						$_saved  = true;
						$message = sprintf( __( "%s を追加設定しました。", self::DOMAIN ), $add_ip_address );
					}
				}
			} elseif ( $act == "truncate" ) {
				// スパムデータテーブルのtruncateを行う
				$result = $wpdb->query(
					"TRUNCATE TABLE " . $this->table_name );
				if ( $result == true ) {
					$_saved  = true;
					$message = __( "スパムデータをすべて削除しました。", self::DOMAIN );
				} else {
					$_saved  = true;
					$message = __( "スパムデータテーブルへ削除処理を実行しましたが、エラーが発生し処理が完了しませんでした。", self::DOMAIN );
				}
			}
		}
		?>
        <div class="wrap">
			<?php
			if ( get_option( 'tsa_spam_data_save' ) == '1' ) {
				// 日数
				$gdays = get_option( 'tsa_spam_keep_day_count', $df_spam_keep_day_cnt );
				if ( $gdays < $lower_spam_keep_day_cnt ) {
					$gdays = $lower_spam_keep_day_cnt;
				}
				// 表カラー
				$unique_color = "#114477";
				$web_color    = "#3377B6";
				?>
                <h2><?php _e( "Throws SPAM Away スパムデータ", self::DOMAIN ); ?></h2>
                <h3><?php echo sprintf( __( "スパム投稿%d日間の推移", self::DOMAIN ), $gdays ); ?></h3>
			<?php if ( $_saved ) { ?>
                <div class="updated" style="padding: 10px; width: 50%;"
                     id="message"><?php esc_attr_e( $message ); ?></div>
			<?php } ?>
                <div style="background-color: #efefef;">
                    <table style="width: 100%; border: none;">
                        <tr>
							<?php
							$total_qry = "
						SELECT count(ppd) as pageview, ppd
						FROM (select ip_address, SUBSTRING(post_date,1,10) as ppd from $this->table_name) as A
						GROUP BY ppd HAVING ppd >= '" . gmdate( 'Y-m-d', current_time( 'timestamp' ) - 86400 * $gdays ) . "'
				ORDER BY pageview DESC
				LIMIT 1
				";
							$qry       = $wpdb->get_row( $total_qry );
							$maxxday   = 0;
							if ( $qry ) {
								$maxxday = $qry->pageview;

								$total_vis = "
							SELECT count(distinct ip_address) as vis, ppd
							FROM (select ip_address, SUBSTRING(post_date,1,10) as ppd from $this->table_name) as B
							GROUP BY ppd HAVING ppd >= '" . gmdate( 'Y-m-d', current_time( 'timestamp' ) - 86400 * $gdays ) . "'
						ORDER BY vis DESC
						LIMIT 1
						";
								$qry_vis   = $wpdb->get_row( $total_vis );
								$maxxday   += $qry_vis->vis;
							}

							if ( $maxxday == 0 ) {
								$maxxday = 1;
							}

							// Y
							$gd = ( 100 / $gdays ) . '%';
							for ( $gg = $gdays - 1; $gg >= 0; $gg -- ) {
								// TOTAL SPAM COUNT
								$visitor_qry  = "
		SELECT count(DISTINCT ip_address) AS total
		FROM (select ip_address, SUBSTRING(post_date,1,10) as ppd from $this->table_name) as B
		WHERE ppd = '" . gmdate( 'Y-m-d', current_time( 'timestamp' ) - 86400 * $gg ) . "'
								";
								$qry_visitors = $wpdb->get_row( $visitor_qry );
								$px_visitors  = round( $qry_visitors->total * 100 / $maxxday );
								// TOTAL
								$pageview_qry  = "
		SELECT count(ppd) as total
		FROM (select ip_address, SUBSTRING(post_date,1,10) as ppd from $this->table_name) as C
		WHERE ppd = '" . gmdate( 'Y-m-d', current_time( 'timestamp' ) - 86400 * $gg ) . "'
						";
								$qry_pageviews = $wpdb->get_row( $pageview_qry );
								$px_pageviews  = round( $qry_pageviews->total * 100 / $maxxday );
								$px_white      = 100 - $px_pageviews - $px_visitors;
								if ( $px_white < 0 ) {
									$px_white = 0;
								}

								print "<td width='$gd' valign='bottom'><div style='float:left;width:100%;font-family:Helvetica;font-size:7pt;text-align:center;border-right:1px solid white;color:black;'>
					<div style='background:#ffffff;width:100%;height:" . $px_white . "px;'></div>
					<div style='background:$unique_color;width:100%;height:" . $px_visitors . "px;' title='" . $qry_visitors->total . " ip_addresses'></div>
					<div style='background:$web_color;width:100%;height:" . $px_pageviews . "px;' title='" . $qry_pageviews->total . " spam comments'></div>
					<div style='background:gray;width:100%;height:1px;'></div>
					<br />" . gmdate( 'd', current_time( 'timestamp' ) - 86400 * $gg ) . '<br />' . gmdate( 'M', current_time( 'timestamp' ) - 86400 * $gg ) . "
					<div style='background:;width:100%;height:2.2em;'>" . $qry_visitors->total . "<br />" . $qry_pageviews->total . "</div>
					<br clear=\"all\" /></div>
					</td>\n";
							} ?>
                        </tr>
                    </table>
                </div>
			<?php _e( "&nbsp;※&nbsp;数値は&lt;上段&gt;がSPAM投稿したユニークIPアドレス数、&nbsp;&lt;下段&gt;が破棄したスパム投稿数", self::DOMAIN ); ?>
            <br>

			<?php
			// wp_tsa_spam の ip_address カラムに存在するIP_ADDRESS投稿は無視するか
			$results = $wpdb->get_results(
				"SELECT D.cnt as cnt,E.ip_address as ip_address, D.ppd as post_date, E.error_type as error_type, E.author as author, E.comment as comment FROM
	((select count(ip_address) as cnt, ip_address, max(post_date) as ppd, error_type, author, comment from $this->table_name
	WHERE post_date >= '" . gmdate( 'Y-m-d', current_time( 'timestamp' ) - 86400 * $gdays ) . "'
	GROUP BY ip_address) as D INNER JOIN $this->table_name as E ON D.ip_address = E.ip_address AND D.ppd = E.post_date)
	ORDER BY post_date DESC"
			);
			?>
                <h4>
					<?php echo sprintf( __( "過去%d日間に無視投稿されたIPアドレス", self::DOMAIN ), $gdays ); ?>
                </h4>
                <p>※IPアドレスをクリックすると特定のホストが存在するか確認し存在する場合は表示されます。</p>
                <p>「スパムデータから削除する」ボタンを押しますと該当IPアドレスのスパム投稿データが削除されます。テストしたあとの削除などに使用してください。</p>
			<?php if ( count( $results ) > 0 ) {
			$p_url = WP_PLUGIN_URL . '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) );
			wp_enqueue_script( 'jquery.tablesorter', $p_url . 'js/jquery.tablesorter.min.js', array( 'jquery' ), false );
			wp_enqueue_style( 'jquery.tablesorter', $p_url . 'images/style.css' );
			wp_enqueue_style( 'thorows-spam-away-styles', $p_url . 'css/tsa_data_styles.css' );
			?>
                <script type="text/JavaScript">
                    <!--
                    jQuery(function () {
                        jQuery('#spam_list').tablesorter({
                            widgets: ['zebra'],
                            headers: {
                                0: {id: "ipAddress"},
                                1: {sorter: "digit"},
                                2: {sorter: "shortDate"},
                                3: {sorter: false}
                            }
                        });
                    });

                    function removeIpAddressOnData(ipAddressStr) {
                        if (confirm('[' + ipAddressStr + '] <?php _e( "をスパムデータベースから削除します。よろしいですか？この操作は取り消せません", self::DOMAIN ); ?>')) {
                            jQuery('#remove_ip_address').val(ipAddressStr);
                            jQuery('#remove').submit();
                        } else {
                            return false;
                        }
                    }

                    function addIpAddressOnData(ipAddressStr) {
                        if (confirm('[' + ipAddressStr + '] <?php _e( "を無視対象に追加します。よろしいですか？削除は設定から行ってください", self::DOMAIN ); ?>')) {
                            jQuery('#add_ip_address').val(ipAddressStr);
                            jQuery('#adding').submit();
                        } else {
                            return false;
                        }
                    }

                    -->
                </script>
                <p><strong><?php _e( "投稿内容の判定", self::DOMAIN ); ?></strong></p>
			<?php _e( "※最新1件のコメント内容はIPアドレスまたはエラー判定のリンク先を参照してください。", self::DOMAIN ); ?>
                <div id="spam_list_container">
                    <div id="spam_list_div">
                        <table id="spam_list" class="tablesorter">
                            <colgroup class="cols0"></colgroup>
                            <colgroup class="cols1"></colgroup>
                            <colgroup class="cols2"></colgroup>
                            <colgroup class="cols3"></colgroup>
                            <colgroup class="cols4"></colgroup>
                            <thead>
                            <tr>
                                <th class="cols0"><?php _e( "IPアドレス", self::DOMAIN ); ?></th>
                                <th class="cols1"><?php _e( "投稿数", self::DOMAIN ); ?></th>
                                <th class="cols2"><?php _e( "最終投稿日時", self::DOMAIN ); ?></th>
                                <th class="cols3"><?php _e( "スパムIP登録", self::DOMAIN ); ?></th>
                                <th class="cols4"><?php _e( "エラー判定（最新）", self::DOMAIN ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php
							foreach ( $results as $item ) {
								$spam_ip         = $item->ip_address;
								$spam_cnt        = $item->cnt;
								$last_post_date  = $item->post_date;
								$spam_error_type = $item->error_type;
								$spam_author     = strip_tags( $item->author );
								$spam_comment    = strip_tags( $item->comment );

								// エラー変換
								$spam_error_type_str = $spam_error_type;
								switch ( $spam_error_type ) {
									case NOT_JAPANESE:
										$spam_error_type_str = __( "日本語以外", self::DOMAIN );
										break;
									case MUST_WORD:
										$spam_error_type_str = __( "必須キーワード無し", self::DOMAIN );
										break;
									case NG_WORD:
										$spam_error_type_str = __( "NGキーワード混入", self::DOMAIN );
										break;
									case BLOCK_IP:
										$spam_error_type_str = __( "ブロック対象IPアドレス", self::DOMAIN );
										break;
									case SPAM_BLACKLIST:
										$spam_error_type_str = __( "スパム拒否リスト", self::DOMAIN );
										break;
									case SPAM_TRACKBACK:
										$spam_error_type_str = __( "トラックバックスパム", self::DOMAIN );
										break;
									case URL_COUNT_OVER:
										$spam_error_type_str = __( "URL文字列混入数オーバー", self::DOMAIN );
										break;
									case SPAM_LIMIT_OVER:
										$spam_error_type_str = __( "一定時間スパム判定エラー", self::DOMAIN );
										break;
									case DUMMY_FIELD:
										$spam_error_type_str = __( "ダミー項目エラー", self::DOMAIN );
										break;
									case NOT_IN_WHITELIST_IP:
										$spam_error_type_str = __( "許可リスト許可IP以外", self::DOMAIN );
										break;
								}
								?>
                                <tr>
                                    <td>
                                        <b><a href="javascript:void(0);"
                                              onclick="window.open('<?php echo esc_attr( $p_url ); ?>hostbyip.php?ip=<?php esc_attr_e( $spam_ip ); ?>', 'hostbyip', 'width=350,height=500,scrollbars=no,location=no,menubar=no,toolbar=no,directories=no,status=no');"><?php esc_attr_e( $spam_ip ); ?>
                                            </a></b><br clear="all">
                                        <input type="button"
                                               onclick="javascript:removeIpAddressOnData('<?php esc_attr_e( $spam_ip ); ?>');"
                                               value="<?php _e( "スパムデータから削除する", self::DOMAIN ); ?>">
                                    </td>
                                    <td><?php esc_attr_e( $spam_cnt ); ?>回</td>
                                    <td><?php esc_attr_e( $last_post_date ); ?></td>
                                    <td>
										<?php // if ( ! in_array( $spam_ip, $ip_list ) ) { ?>
                                        <input type="button"
                                               onclick="javascript:addIpAddressOnData('<?php esc_attr_e( $spam_ip ); ?>');"
                                               value="<?php _e( "ブロック対象IPアドレス追加", self::DOMAIN ); ?>[<?php esc_attr_e( $spam_ip ); ?>]">
										<?php /*} else { ?>
								ブロック対象IP
<?php } */ ?>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0);"
                                           onclick="window.open('<?php echo esc_attr( $p_url ); ?>hostbyip.php?ip=<?php esc_attr_e( $spam_ip ); ?>', 'hostbyip', 'width=350,height=500,scrollbars=no,location=no,menubar=no,toolbar=no,directories=no,status=no');"><?php esc_attr_e( $spam_error_type_str ); ?>
                                        </a>
                                    </td>
                                </tr>
								<?php
							}
							?>
                            </tbody>
                        </table>
                    </div>
                </div>
			<?php } ?>
			<?php } else { ?>
                <p><?php _e( 'スパムデータベースを使用するにはThrows SPAM Awayのメニュー「設定」から<br>「スパムコメント投稿情報を保存しますか？」項目を<strong>「スパムコメント情報を保存する」</strong>に設定してください', self::DOMAIN ); ?></p>
			<?php } ?>
            <form method="post" id="remove">
                <input type="hidden" name="ip_address" id="remove_ip_address" value="">
                <input type="hidden" name="act" value="remove_ip">
				<?php wp_nonce_field( 'tsa_action', 'tsa_nonce' ) ?>
            </form>
            <form method="post" id="adding">
                <input type="hidden" name="ip_address" id="add_ip_address" value="">
                <input type="hidden" name="act" value="add_ip">
				<?php wp_nonce_field( 'tsa_action', 'tsa_nonce' ) ?>
            </form>
            <p>スパム投稿IPアドレスを参考にアクセス禁止対策を行なってください。</p>
            <form method="post" id="adding">
                <input type="hidden" name="act" value="truncate">
				<?php
				$other_attributes = array( 'onclick' => "return confirm('" . __( 'すべてのスパムデータが削除されます。よろしいですか？', self::DOMAIN ) . "');" );
				submit_button( __( 'すべてのデータを削除する', self::DOMAIN ), 'delete', 'wpdocs-save-settings', true, $other_attributes );
				?>
				<?php wp_nonce_field( 'tsa_action', 'tsa_nonce' ) ?>
            </form>
        </div>
        <br clear="all">
		<?php
	}

	/**
	 *    おすすめ設定ページ
	 */
	function recommend_setting() {
//		global $wpdb;
		?>
        制作中
		<?php
	}

}