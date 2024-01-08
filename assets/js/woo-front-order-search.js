jQuery(document).ready(function(){
    var tracking_url = '';
   jQuery(".search-btn").on('click',function(){
       jQuery(this).parents('.woo_front_order_search').find(".woo_front_order_response").addClass('hide').removeClass('error').find('span').text("");
        var id = jQuery(this).parents('.woo_front_order_search').attr("data-id");
       jQuery(".woo_front_order_table#wft-"+id).hide();
      //alert('OK'); 
      var order_id = jQuery(this).parents('.woo_front_order_search').find(".search-input").val();
      console.log(id);
      var that =  jQuery(this);
      console.log(order_id);
      if(order_id != ''){
          jQuery(this).parents('.woo_front_order_search').find('button.search-btn').attr('disabled','disabled');
          //console.log(order_id);
          jQuery.post(
            "https://xn--t8j8lqbvdu541d.jp/wp-admin/admin-ajax.php",
            {
              action: "woo_front_search_order",
              data: { "order_id" : order_id },
            },
            function (response) {
              //console.log(response);
              jQuery(that).parents('.woo_front_order_search').find('button.search-btn').removeAttr('disabled');
              if (response.success) {
                //console.log(response.data.order_id);
                if(response.data.shipping == 'DHL'){
                    tracking_url = 'https://www.dhl.co.jp/ja/express/tracking.html';
                }else{
                    tracking_url = "https://trackings.post.japanpost.jp/services/srv/search/direct?searchKind=S004&locale=ja&reqCodeNo1=";
                }
                jQuery(".woo_front_order_table#wft-"+id).find("td.order_id-td").text(response.data.order_id);
                jQuery(".woo_front_order_table#wft-"+id).find("td.order_status-td").text(response.data.status);
                var pstatus = "";
                if( response.data.tracking_number !=''){
                    if(response.data.en_status == "partially-shipped"){
                        pstatus = "<br>その他の商品につきましては、発送が完了され次第、追跡情報をお知らせいたします。 ";
                    }
                    console.log(response.data.tracking_number.split(','));
                    var track = response.data.tracking_number.split(',');
                    var track_html = "";
                    for(let i=0;i<track.length;i++){
                        track_html += "<a class='order_tracking-td' href='"+tracking_url+track[i]+"' target='_blank'>"+track[i]+"</a> <br/>";
                    }
                    jQuery(".woo_front_order_table#wft-"+id).find(".tracking-td").html("以下の追跡番号をクリックすると、お荷物の追跡情報の詳細を閲覧することができます。<br/><span>"+track_html+"</span>※番号から追跡情報の反映までには７日～１０日ほどの時間がかかることがあります。" + pstatus);
                }else{
                    jQuery(".woo_front_order_table#wft-"+id).find(".tracking-td").text("ご注文の確認と、商品の発送に向けた準備をしています。発送が完了しましたらお荷物の追跡番号をお知らせいたしますので、今しばらくお待ちお願いします。");
                }
                jQuery(".woo_front_order_table#wft-"+id).slideDown();
                // jQuery("html, body").animate(
                //   { scrollTop: jQuery("#woo_front_order_table").offset().top - 100 },
                //   "slow"
                // );
              }else{
                  //console.log('false');
                  jQuery(that).parents('.woo_front_order_search').find(".woo_front_order_response").removeClass('hide').addClass('error').find('span').text(response.data);
              }
              
            }
          );
      }
   }); 
});