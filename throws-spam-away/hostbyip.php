<?php
/**
 * <p>ThrowsSpamAway</p> hostbyipページ
 * WordPress's Plugin
 * @author Takeshi Satoh@GTI Inc. 2017
 * @version 3.0
 */
require_once 'throws_spam_away.class.php';
require_once dirname( __FILE__ ) . '/../../../wp-load.php';
/**
 * ホスト検索
 */
$spam_ip = htmlspecialchars( $_GET['ip'] );
$newThrowsSpamAway = new ThrowsSpamAway();
$last_spam_comment_result = $newThrowsSpamAway->get_last_spam_comment( $spam_ip );
// 最終投稿日
$last_comment_date = $last_spam_comment_result->post_date;
$last_comment_post = get_permalink( $last_spam_comment_result->post_id );
$last_comment_post_title = get_the_title( get_post( $last_spam_comment_result->post_id ) );
$is_spam_champuru = ( $newThrowsSpamAway->reject_spam_ip( $spam_ip ) ? false : true );
$spam_author = $last_spam_comment_result->spam_author;
$spam_comment = esc_attr( $last_spam_comment_result->spam_comment );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

    <title>Throws SPAM Away | Hostbyip[<?php echo htmlspecialchars( $_GET['ip'] ); ?>]</title>
    <script type="text/javascript">
        window.onblur = function () {
            window.close();
        }
    </script>
    <style>
        h2 {
            background: #333;
            color: #fff;
        }

        h3 {
            background: #666;
            color: #fff;
        }

        .tsa_hostbyip_text {
            background: #999;
            color: #fff;
            margin: 3px 0 0 0;
        }
    </style>
</head>
<body>
<div style="textalign: center;">
    <h2><?php echo $spam_ip; ?></h2>
	<?php
	$spam_host = gethostbyaddr( htmlspecialchars( $spam_ip ) );
	if ( $spam_host != $spam_ip ) {
		?>
        <h3><?php _e( "特定のホスト情報が見つかりました。", 'throws-spam-away' ); ?></h3>
        ↓↓↓<br/>

        <h4><?php echo $spam_host; ?></h4>
        Whois: <a href="http://whois.arin.net/rest/ip/<?php echo $spam_ip; ?>"
                  target="_blank"><?php echo $spam_ip; ?></a>
		<?php
	} else {
		?>
        <h3><?php _e( "このIPアドレスから特定のホスト情報は見つかりませんでした。", 'throws-spam-away' ); ?></h3>
		<?php
	}
	?>
	<?php if ( $last_spam_comment_result != null ) { ?>
        <div class="tsa_hostbyip_text"><?php _e( "このIPからの最終投稿日時", 'throws-spam-away' ); ?></div><?php echo $last_comment_date; ?>
        <br/>
        <div class="tsa_hostbyip_text"><?php _e( "このIPからスパム投稿対象となったページ", 'throws-spam-away' ); ?></div><a
                href="<?php echo $last_comment_post; ?>" target="_blank"><?php echo $last_comment_post_title; ?></a>
        <br/>
	<?php } ?>
    <h4><?php _e( "スパムフィルター：", 'throws-spam-away' ); ?><?php echo( $is_spam_champuru ? __( "スパム拒否リスト存在IPアドレス", 'throws-spam-away' ) : __( "未検出", 'throws-spam-away' ) ); ?></h4>

    <div class="tsa_hostbyip_text"><?php _e( "最新コメント内容", 'throws-spam-away' ); ?></div>
    <div><?php if ( $spam_author != null && $spam_comment != null ) {
			?>
            IP: <?php esc_attr_e( $spam_ip ); ?><br/>
            名前：<?php echo esc_attr( $spam_author ); ?><br/>
            内容：<?php esc_attr_e( $spam_comment ); ?><?php
		} ?>
    </div>

    <div style="text-align:right;"><a href="javascript:void(0);"
                                      onclick="window.close();"><?php _e( "閉じる", 'throws-spam-away' ); ?></a></div>
</div>
</body>
</html>