<?php
/**
 * <p>ThrowsSpamAway</p> Comments Class
 * WordPress's Plugin
 * @author Takeshi Satoh@GTI Inc. 2019
 * @version 3.1.1
 */

require_once "throws_spam_away.class.php";

function tsa_comment_main() {
	global $wpdb;
	$c_all   = $wpdb->query( "SELECT comment_ID FROM $wpdb->comments" );
	$c_pend  = $wpdb->query( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved = '0'" );
	$s_count = $wpdb->query( "SELECT comment_ID FROM $wpdb->comments WHERE comment_approved =  'spam'" );
	?>

    <div class="wrap">
        <h2>スパムコメント処理</h2>
        <p>Akismet などのコメントフィルタによって spam マークがついているコメントを削除します。</p>
		<?php if ( $_POST['c_all'] == 'a' && $_POST['all'] == 's' ) {
			$spam_comment_count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved =  'spam'" );
			if ( $spam_comment_count == 0 ) {
				echo "<p style='color:green'><strong>" . __( 'You have no comment spam.', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
			} else {
				$delete_flg = $wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_approved =  'spam'" );
				if ( $delete_flg != false ) {
					echo "<p style='color:green'><strong>" . __( 'Spam comments have been deleted.', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
				} else {
					echo "<p style='color:red'><strong>" . __( 'Something Went Wrong,Please Try Again!', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
				}
			}
		} elseif ( $_POST['c_all'] == 'a' && $_POST['all'] == 'a' ) {
			if ( $wpdb->query( "DELETE FROM $wpdb->comments" ) != false ) {
				$wpdb->query( "DELETE FROM $wpdb->comments" );
				echo "<p style='color:green'><strong>" . __( 'All comments have been deleted.', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
			} else {
				echo "<p style='color:red'><strong>" . __( 'Something Went Wrong,Please Try Again!', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
			}
		}
		?>
		<?php
		echo "<h4>" . __( 'Number of all Comments', ThrowsSpamAway::DOMAIN ) . " : " . $c_all . " </h4>";
		echo "<h4>" . __( 'Number of Spam Comments', ThrowsSpamAway::DOMAIN ) . " : " . $s_count . "</h4>";
		?>

		<?php if ( $c_all > 0 ) { ?>
            <form name="dce" method="post">
                <input type="hidden" name="c_all" value="a">
                <input type="radio" name="all"
                       value="s"> <?php _e( 'Delete all spam comments', ThrowsSpamAway::DOMAIN ); ?><br>
                <input type="radio" name="all" value="a"> <?php _e( 'Delete all comments', ThrowsSpamAway::DOMAIN ); ?>
                <br>
                <p class="submit">
                    <input type="submit" name="Submit"
                           value="<?php _e( 'Delete all Comments', ThrowsSpamAway::DOMAIN ); ?>"
                           onclick="return confirm('<?php _e( 'I will send. Is it OK?', ThrowsSpamAway::DOMAIN ); ?>');">
                </p>
            </form>
			<?php
		} else {
			echo "<p><strong>" . __( 'All comments have been deleted.', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
		}
		?>
		<?php if ( $_POST['c_pend'] == 'p' && $_POST['pend'] == 'p' ) {
			if ( $wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_approved = '0'" ) != false ) {
				$wpdb->query( "DELETE FROM $wpdb->comments WHERE comment_approved = '0'" );
				echo "<p style='color:green'><strong> " . __( 'All Pending comments have been deleted.', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
			} else {
				echo "<p style='color:red'><strong>" . __( 'Something Went Wrong,Please Try Again!', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
			}
		} else {
			?>
			<?php echo "<h4>" . __( 'Number of Pending Comments', ThrowsSpamAway::DOMAIN ) . " : " . $c_pend . " </h4>"; ?>

			<?php if ( $c_pend > 0 ) { ?>
                <form name="dcep" method="post"
                      action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
                    <input type="hidden" name="c_pend" value="p">
                    <input type="checkbox" name="pend"
                           value="p"> <?php _e( 'Delete all pending comments', ThrowsSpamAway::DOMAIN ); ?>
                    <p class="submit">
                        <input type="submit" name="Submit"
                               value="<?php _e( 'Delete all pending Comments', ThrowsSpamAway::DOMAIN ); ?>">
                    </p>
                </form>
				<?php
			} else {
				echo "<p><strong>" . __( 'All pending comments have been deleted.', ThrowsSpamAway::DOMAIN ) . "</strong></p>";
			}
		}
		?>
        <h4><?php _e( 'Warning : Once Comment Deleted can\'t be restored!', ThrowsSpamAway::DOMAIN ); ?></h4>
    </div>
	<?php
}

// プロセス
tsa_comment_main();