$(document).ready(function(){
	doLayout();
	locateCurrentAddressBaidu();
	
	switchShopImage();
	
	$(window).scroll(function() {
	    var scrollTop = $(document).scrollTop();
	    var docHeight = $(document).height();
	    var winHeight = $(window).height();
	    
	    if(docHeight>winHeight) {
		    if((docHeight-scrollTop-winHeight) < 20) {
		    	if(load_more_callback && (!in_loading_more)) {
		    		in_loading_more = true;
		    		load_more_callback(function(){
		    			in_loading_more = false;
		    		});
		    	}
		    }
		}
	    
	});
	
	var iframe = document.createElement("IFRAME");
	iframe.setAttribute("src", 'data:text/plain,');
	document.documentElement.appendChild(iframe);
	iframe.style.display='none';
	
	//window.alert = function(str) {
	//	window.frames[0].window.alert(str);
	//}
	
	
});

$(window).resize(function(){
	doLayout();
});

var currentPosition = null;
var currentAddress = null;
var city = "北京市";
var dinnerNavArray = new Array("今日午餐","今日晚餐","明日午餐","明日晚餐");
var dinnerNavId = 0;
var mapSearching = false;
var mapSearchKeyword = "";
var mapLocalSearch;
var vCodeCounter = 6;
var vCodeEnabled = true;
var backStack = new Array();
var addressHistory = new Array();
var order = {};
var currentShopId = 0;
var total_fee = 0;
var product_fee = 0;
var user_avatar = "images/default_avatar.png";
var load_more_callback = null;
var in_loading_more = false;
var keeper_intro_height = 34;

//key-product_id value-count
var cartItems = new Array();
var productItems = new Array();

function setOnLoadMore(_callback) {
	load_more_callback = _callback;
}

function closeIndexNotice() {
	$("#IndexNotice").hide();
}

function callServer(method,params,success,error) {
	var url = "client/api.php?method="+method+"&t="+(new Date()).getTime();
	$.ajax(url,{
		type:'POST',
		data:params,
		dataType:'json',
		success:function(data) {
			if(data.success) {
				if(success) success(data);
			} else {
				if(error) {
					error(data.errcode,data.errmsg);
				} else {
					alert(data.errmsg);
				}
			}
		},
		error:function(xhr,type,exception) {
			if(error) {
				error(9999,"网络异常");
			} else {
				if(xhr.readyState!=4) {
					alert("网络异常");
				} else {
					alert("未知错误");
				}
				
			}
		}
	});
}

function locateCurrentAddressBaidu() {
	var geo = new BMap.Geolocation();
	var geoc = new BMap.Geocoder();
	geo.getCurrentPosition(function(r){
		if(this.getStatus() == BMAP_STATUS_SUCCESS){
			var pt = r.point;
			currentPosition = {lng:pt.lng,lat:pt.lat};
			geoc.getLocation(pt,function(rs){
				var addrComp = rs.addressComponents;
				$("#AddressText").text(addrComp.street+addrComp.streetNumber);
				
				if(addrComp.city) {
					city = rs.addressComponents.city;
				}

				currentAddress = rs.address;

			});
			clickDinnerNav(dinnerNavId);
			
		} else {
			$("#AddressText").text("定位发生问题");
		}        
	},{enableHighAccuracy: true});
}

var mapAutocomplete;
var addressEditorPosition = null;

function initAddressAutocomplete() {
	if(mapAutocomplete) return;
	
	mapAutocomplete = new BMap.Autocomplete({
		"input":"AddressEditorAddress",
		"location":city
	});
	
	var myValue;
	
	mapAutocomplete.addEventListener("onconfirm",function(e){
		var _value = e.item.value;
		myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
		//addressEditorPosition = null;
		var local = new BMap.LocalSearch(city, {
			onSearchComplete: function(){
				var pt = local.getResults().getPoi(0).point;
				addressEditorPosition = {lng:pt.lng,lat:pt.lat};
			}
		});
		local.search(myValue);
	});
	
}

function locateCurrentPosition() {
	$("#AddressText").html("定位中");
	window.history.back();
	locateCurrentAddressBaidu();
}



function clickDinnerNav(id) {
	dinnerNavId = id;
	var html = "";

	
	for(var i=0;i<dinnerNavArray.length;i++) {
		if(i==id) {
			html += "<li class='Selected' onclick='clickDinnerNav("+id+");'>"+dinnerNavArray[id]+"</li>";
		} else {
			html += "<li onclick='clickDinnerNav("+i+");'>"+dinnerNavArray[i]+"</li>";
		}
	}
	
	$("#DinnerNav").html(html);
	var params = {};
	if(currentPosition) {
		params.lng = currentPosition.lng;
		params.lat = currentPosition.lat;
	}
	params.time_type = dinnerNavId;
	var offset = 0;
	var count = 10;
	$("#ProductList").html("");
	
	var loadIndex = function(done) {
		params.offset = offset;
		params.count = count;
		callServer("get_index_list", params ,function(data){
			$("#ProductList").append(data.html);
			if(data.html!="") offset += count;
			if(done) done();
		},function(errcode,errmsg){
			if(done) done();
			alert(errmsg);
		});
	}
	
	if($("#Index").css('display')!='none') {
		setOnLoadMore(function(done){
			loadIndex(done);
		});
	}
	
	loadIndex();
	
}



function incCartItem(product_id,is_half,stock) {
	changeCartItem(product_id,1,is_half,stock);
}

function decCartItem(product_id,is_half,stock) {
	changeCartItem(product_id,-1,is_half,stock);
}

function changeCartItem(product_id,count,is_half,stock) {
	var len = cartItems.length;
	var found = false;
	for(var i=0;i<len;i++) {
		var item = cartItems[i];
		if(product_id==item.product_id) {
			if(count>0 && item.count+count>stock) {
				alert("库存不足");
				return;
			}
			item.count += count;
			if(item.count==0) {
				if(is_half) {
					item.count = 0.5;
				} else {
					cartItems.splice(i,1);
				}
			} else if(item.count < 0){
				cartItems.splice(i,1);
			} else if(item.count==1.5) {
				item.count = 1;
			}
			found = true;
			break;
		}
	}
	if(!found) {
		var item = {product_id:product_id,count:is_half?0.5:1};
		cartItems.unshift(item);
	}
	
	updateCart();
}

function getProductById(product_id) {
	var len = productItems.length;
	for(var i=0;i<len;i++) {
		var item = productItems[i];
		if(item.product_id==product_id) {
			return item;
		}
	}
	return null;
}

function getCartItemById(product_id) {
	var len = cartItems.length;
	for(var i=0;i<len;i++) {
		var item = cartItems[i];
		if(item.product_id==product_id) {
			return item;
		}
	}
	return null;
}

function updateCart() {
	var len = productItems.length;
	var cartLen = cartItems.length;
	var sum = 0;
	for(var i=0;i<len;i++) {
		var item = productItems[i];
		var cartItem = getCartItemById(item.product_id);
		if(cartItem) {
			$("#productListCount_"+item.product_id).text(cartItem.count);
			$("#productListCount_"+item.product_id).show();
			$("#productListMinus_"+item.product_id).show();
			sum += item.product_price*cartItem.count;
		} else {
			$("#productListCount_"+item.product_id).text(0);
			$("#productListCount_"+item.product_id).hide();
			$("#productListMinus_"+item.product_id).hide();
		}
	}
	$("#CartSum").text("合计：￥"+sum);
	if(cartLen==0) {
		$("#Cart").show();
	} else {
		$("#Cart").show();
	}
}


function doLayout() {
	$("#Mine").height($(window).height());
}


window.onpopstate = function (data) {
	var backStackFrame = backStack.pop();
	if(backStackFrame && backStackFrame._callback) {
		backStackFrame._callback(backStackFrame);
	}
}

function clearBackStack() {
	backStack = new Array();
}

function showIndex() {
	$("#Mine").hide();
	$("#Order").hide();
	if($("body").scrollTop()!=0) $("body").scrollTop(0);
	$("#Index").show();
	clickDinnerNav(dinnerNavId);
}

function showOrder() {
	$("#Index").hide();
	$("#Mine").hide();
	if($("body").scrollTop()!=0) $("body").scrollTop(0);
	$("#Order").show();
	
	var params = {}
	var offset = 0;
	var count = 10;
	
	$("#MyOrderList").html("");
	
	var loadOrder = function(done) {
		params.offset = offset;
		params.count = count;
		callServer("get_order_list", params, function(data){
			$("#MyOrderList").append(data.html);
			if(data.html!="") offset += count;
			if(data.html=="" && offset==0) {
				$("#MyOrderList").html('<div class="noDataList"><img src="images/no_order.png"/></div>');
			}
			if(done) done();
		});
	};
	
	setOnLoadMore(function(done){
		loadOrder(done);
	});
	
	loadOrder();
	
}

function showMine() {
	checkLogin();
	$("#Index").hide();
	$("#Order").hide();
	if($("body").scrollTop()!=0) $("body").scrollTop(0);
	$("#Mine").show();
	$("#LoginPhone").width($(window).width()-30);
	$("#LoginVCode").width($(window).width()-140);
}

function pushFrame(oldFrameId,newFrameId,_extra_callback) {
	history.pushState(oldFrameId,oldFrameId);
	backStack.push({
		oldFrameId:oldFrameId,
		newFrameId:newFrameId,
		top:$("body").scrollTop(),
		_callback:function(stack) {
			$("#"+newFrameId).hide();
			$("#"+oldFrameId).show();
			$("body").scrollTop(stack.top);
			setOnLoadMore(stack._load_more_callback);
			if(stack._extra_callback) stack._extra_callback();
		},
		_extra_callback:_extra_callback,
		_load_more_callback:load_more_callback
	});
	$("#"+oldFrameId).hide();
	$("body").scrollTop(0);
	setOnLoadMore(null);
	$("#"+newFrameId).show();
}

function showPickAddress() {
	pushFrame("Index","PickAddress",function(){
		$("#AddressInput").hide();
	});
	loadAddressHistory();
}

function showBannerActivity() {
/*	callServer("get_special_user_id",{}, function(data){
		if(data.user_id==990 || data.user_id==10) {
			pushFrame("Index","activity_1024",function(){
				$("#AddressInput").hide();
			});	
		} else {
			return;
		}
	});
*/
	pushFrame("Index","activity_1024",function(){
		$("#AddressInput").hide();
	});	

}

function showShop(shop_id,fromFrame) {
	var params = {};
	params.shop_id = shop_id;
	params.time_type = dinnerNavId;
	if(currentPosition) {
		params.lng = currentPosition.lng;
		params.lat = currentPosition.lat;
	}
	
	callServer("get_shop_detail", params, function(data){
		cartItems = new Array();
		currentShopId = shop_id;
		pushFrame(fromFrame?fromFrame:"Index","Shop");
		$("#Shop").html(data.html);
		switchShopSeed = 0;
		
		var setShopImage = function() {
			var img_height = $("#ShopCover > img").height();
			if(img_height==0) {
				setTimeout(setShopImage,10);
			} else {
				$("#ShopCover").height(img_height);
			}
		};
		
		setShopImage();
		
		keeper_intro_height = $("#KeeperIntro").height();
		if(keeper_intro_height>36) {
			$("#KeeperIntro").height(36);
			$("#KeeperIntroMoreContainer").show();
		}
		
	});
}

function slideKeeperIntroDown() {
	$("#KeeperIntro").height(keeper_intro_height);
	$("#KeeperIntroMoreContainer").hide();
}

var switchShopSeed = 0;

function switchShopImage () {
	if($("#Shop").css('display')!='none') {
		var count = $("#ShopCover > img").length;
		if(count>1) {
			var index = switchShopSeed % count;
			var lastIndex = (index==0)?(count-1):(index-1);
			$("#ShopCover > img:eq("+lastIndex+")").fadeOut();
			$("#ShopCover > img:eq("+index+")").fadeIn();
			switchShopSeed++;
		}
	}
	setTimeout(switchShopImage,2000);
}

function initMapLocalSearch() {
	if(mapLocalSearch) return;
	mapLocalSearch = new BMap.LocalSearch(city,{
		onSearchComplete:function(rs) {
			if(rs!=null) {
				console.log(rs);
				var count = rs.getCurrentNumPois();
				var listHTML = "";
				for(var i=0;i<count;i++) {
					var poi = rs.getPoi(i);
					console.log(poi);
					listHTML += "<li onclick=\"selectAddressFromSearch('"+poi.title+"','"+poi.address+"',"+poi.point.lng+","+poi.point.lat+");\"><b>"+poi.title+"</b><span>"+poi.address+"</span></li>\r\n";
				}
				$("#AddressSuggestionList").html(listHTML);
			}
		}
	});
}

function selectAddressFromSearch(title,address,longitude,latitude) {
	$("#AddressText").text(address);
	currentPosition = {lng:longitude,lat:latitude};
	clickDinnerNav(dinnerNavId);
	$("#AddressInput").hide();
	addAddressHistory({title:title,address:address});
	window.history.back();
}

function startMapSearch() {
	mapSearching = true;
	setTimeout(doMapSearch,1000);
}

function endMapSearch() {
	mapSearching = false;
}

function doMapSearch() {
	if(!mapSearching) return;
	var value = $("#AddressInputBox").val();
	if(value!="") {
		if(value != mapSearchKeyword) {
			mapLocalSearch.search(value);
			mapSearchKeyword = value;
		}
	}
	setTimeout(doMapSearch,1000);
}

function loadAddressHistory() {
	if(window.localStorage) {
		var json_string = window.localStorage.getItem("addressHistory");
		if((!json_string) || json_string=="") {
			json_string = "[]";
		}
		addressHistory = JSON.parse(json_string);
		var len = addressHistory.length;
		var htmlString = "";
		for(var i=0;i<len;i++) {
			var addr = addressHistory[i];
			htmlString += "<li onclick=\"selectAddressHistory("+i+");\"><b>"+addr.title+"</b><span>"+addr.address+"</span></li>";
		}
		$("#AddressHistoryList").html(htmlString);
	}
}

function addAddressHistory(address) {
	if(window.localStorage) {
		var len = addressHistory.length;
		var shouldReplace = false;
		for(var i=0;i<len;i++) {
			var addr = addressHistory[i];
			if(addr.title==address.title) {
				addressHistory[i] = address;
				shouldReplace = true;
				break;
			}
		}
		
		if(!shouldReplace) {
			addressHistory.unshift(address);
		}
		
		var json_string = JSON.stringify(addressHistory);
		window.localStorage.setItem("addressHistory",json_string);
	}
}

function selectAddressHistory(index) {
	if(window.localStorage) {
		showAddressInput();
		var addr = addressHistory[index];
		$("#AddressInputBox").val(addr.title);
		addressHistory.splice(index,1);
		addressHistory.unshift(addr);
		var json_string = JSON.stringify(addressHistory);
		window.localStorage.setItem("addressHistory",json_string);
		loadAddressHistory();
	}
}

function clearAddressHistory() {
	if(window.localStorage) {
		window.localStorage.setItem("addressHistory","[]");
		loadAddressHistory();
	}
}

function showAddressInput() {
	$("#PickAddress").hide();
	$("#AddressInput").show();
	initMapLocalSearch();
	startMapSearch();
	$("#AddressInputBox").val("");
	mapSearchKeyword = "";
	$("#AddressSuggestionList").html("");
	$("#AddressInputBox").focus();
}

function closeAddressInput() {
	$("#PickAddress").show();
	$("#AddressInput").hide();
}

var isTakeawayValue = false;
var isFirstOrder = false;
var isFirstPreOrder = false;

function toggleTakeawayChecker() {
	isTakeawayValue = !isTakeawayValue;
	setTakeawayChecker(isTakeawayValue);
	
}

function updateOrderAmount() {
	discountAmount = (isFirstOrder?first_order_discount:0) ;
	if(!isFirstOrder) {
		discountAmount += selectedCouponAmount;
/*		if(isFirstPreOrder) {
			if(dinnerNavId==2 || dinnerNavId==3) {
				discountAmount += pre_order_discount;
			}			
		}
*/	
	}
	total_fee = product_fee + deliveryFee - discountAmount;
	
	if(total_fee<0) total_fee = 0;
	
	$("#OrderConfirmationDiscount").html("已优惠"+discountAmount+"元");
	$("#OrderConfirmationAmount").html("还需付￥"+total_fee);
}

function setTakeawayChecker(value) {
	isTakeawayValue = value;
	$("#isTakeawayChecker").attr("src",value?"images/check_on.png":"images/check_off.png");
	deliveryFee = isTakeawayValue?0:0;
	/*
	if(isTakeawayValue) {
		$("#deliveryFeeDiv").hide();
		if(isFirstOrder) {
			$("#createOrderSeperator").show();
		} else {
			$("#createOrderSeperator").hide();
		}
	} else {
		$("#deliveryFeeDiv").show();
		$("#createOrderSeperator").show();
	}
	*/
	updateOrderAmount();
}

var oriOrderMemoHTML = null;

function showCreateOrder() {
	
	var cartLen = cartItems.length;
	
	if(cartLen==0) {
		alert("您还没选购菜品呢");
		return;
	}
	
	var hasMain = false;
	for(var i=0;i<cartLen;i++) {
		var item = cartItems[i];
		var product = getProductById(item.product_id);
		if(product.is_main) {
			hasMain = true;
			break;
		}
	}
	
	if(!hasMain) {
		if(!confirm("您尚未点主食，确认不点主食下单吗？")) {
			return;
		}
	}
	
	if(!oriOrderMemoHTML) {
		oriOrderMemoHTML = $("#OrderMemo").html();
	}
	
	$("#OrderMemo").html(oriOrderMemoHTML);
	
	created_order_id = 0;
	callServer("prepare_order", {shop_id:currentShopId,time_type:dinnerNavId}, function(data){
		pushFrame("Shop","CreateOrder");
		$("#OrderAddress").hide();
		$("#OrderAddress").html("");
		$("#NoOrderAddress").show();
		$("#OrderMemoLabel").text("");
		$("#OrderMemoLabel").width($(window).width()-130);
		setTakeawayChecker(false);
		var deliveryTime = data.delivery_time;
		var len = deliveryTime.length;
		var html = "";
		var i;
		for(i=0;i<len;i++) {
			html += "<option value='"+deliveryTime[i].key+"'>"+deliveryTime[i].value+"</option>";
		}
		$("#OrderTimeSelector").html(html);
		
		var coupon_count = data.coupon_count;
		$("#CouponTip").html(coupon_count+"张可用");
		$("#CouponValue").html(coupon_count==0?"没有可用粮票":"未使用");
		
		if(coupon_count==0) $("#CouponTip").hide();
		selectedCouponId = 0;
		
		//pay section
		html = "";
		
		html+="<div id='deliveryFeeDiv' class='Item'>";
		html+="<span>配送费</span>";
		html+="<b>￥0</b>";
		html+="</div>";
		
		if(data.is_first_order) {
			isFirstOrder = true;
			html+="<div class='Item'>";
			html+="<span>首单优惠</span>";
			html+="<b>-￥"+first_order_discount+"</b>";
			html+="</div>";
			
			$("#CouponValue").html("没有可用粮票");
		} else {
			isFirstOrder = false;

/*			if(data.is_first_pre_order) {
				isFirstPreOrder = true;
			
				if(dinnerNavId==2 || dinnerNavId==3) {
					html+="<div class='Item'>";
					html+="<span>提前预定减免</span>";
					html+="<b>-￥"+pre_order_discount+"</b>";
					html+="</div>";
				}				
			} else {
				isFirstPreOrder = false;
			}
*/		}
		
		html+="<div id='createOrderSeperator' class='Seperator'></div>";
		len = cartItems.length;
		var sum = 0;
		for(i=0;i<len;i++) {
			var item = cartItems[i];
			var product = getProductById(item.product_id);
			
			html+="<div class='Item'>";
			html+="<span>"+product.product_name+" x "+item.count+"</span>";
			html+="<b>￥"+product.product_price*item.count+"</b>";
			html+="</div>";
			
			sum += product.product_price*item.count;
		}
		
		$("#OrderPaySection").html(html);
		
		selectedCouponAmount = 0;
		product_fee = sum;
		
		updateOrderAmount();
		var defaultAddressId = data.default_address_id;
		if(defaultAddressId) {
			callServer("get_address_html", {address_id:defaultAddressId}, function(data){
				$("#OrderAddress").html(data.html);
				$("#OrderAddress").show();
				$("#NoOrderAddress").hide();
				$(".AddressChooserTick").attr("src","images/yes_grey.png");
				$("#AddressChooserTick_"+defaultAddressId).attr("src","images/yes.png");
			});
		}
		
	},function(errcode,errmsg) {
		if(errcode==1010) {
			//未登录
			pushFrame("Shop","Login");
		}
	});
	
}

function showAddressChooser(oldFrame) {
	var theFrame = oldFrame?oldFrame:"CreateOrder";
	pushFrame(theFrame,"AddressChooser");
	loadAddressChooserList(theFrame);
	$("#AddressAddButton").attr("onclick","createAddress('"+oldFrame+"');");
}

function showAddressEditor() {
	$("#AddressEditor input").width($("body").width()-120);
	initAddressAutocomplete();
	pushFrame("AddressChooser","AddressEditor",function(){
		//$("#AddressEditorAddress").blur();
		if(mapAutocomplete) {
			mapAutocomplete.hide();
		}
	});
}

function showOrderMemo() {
	pushFrame("CreateOrder","OrderMemo");
	$("#OrderMemoText").width($("body").width()-50);
	$("#OrderMemoText").val($("#OrderMemoLabel").text());
}

function insertOrderMemo(text) {
	var value = $("#OrderMemoText").val();
	var spans = $("#OrderMemoTag > span");
	for (var i=0;i<spans.length;i++) {
		var theSpan = $(spans[i]);
		var spanText = theSpan.text();
		if(text==spanText) {
			var disabled = theSpan.attr("disabled");
			if(theSpan.attr("disabled")) {
				
			} else {
				$("#OrderMemoText").val(value+" "+text);
				theSpan.removeClass("OrderMemoSpanEnabled");
				theSpan.addClass("OrderMemoSpanDisabled");
				theSpan.attr("disabled",true);
				
				if(i==0) {
					$(spans[1]).removeClass("OrderMemoSpanEnabled");
					$(spans[1]).addClass("OrderMemoSpanDisabled");
					$(spans[1]).attr("disabled",true);
				}
				
				if(i==1) {
					$(spans[0]).removeClass("OrderMemoSpanEnabled");
					$(spans[0]).addClass("OrderMemoSpanDisabled");
					$(spans[0]).attr("disabled",true);
				}
				
			}
		}
	}
}

function confirmOrderMemo() {
	window.history.back();
	var value = $("#OrderMemoText").val();
	$("#OrderMemoLabel").text(value);
}

function words_deal() 
{ 
	var value = $("#OrderMemoText").val();
	var spans = $("#OrderMemoTag > span");
	
	if(!(   value.indexOf(($(spans[0])).text()) >= 0
	   	|| value.indexOf(($(spans[1])).text()) >= 0
	    )
	  ) {
		$(spans[0]).removeClass("OrderMemoSpanDisabled");
		$(spans[0]).addClass("OrderMemoSpanEnabled");
		$(spans[0]).attr("disabled",false);
	
		$(spans[1]).removeClass("OrderMemoSpanDisabled");
		$(spans[1]).addClass("OrderMemoSpanEnabled");
		$(spans[1]).attr("disabled",false);
	}

	for (var i=2;i<spans.length;i++) {
		var theSpan = $(spans[i]);
		var spanText = theSpan.text();

		if(!(value.indexOf(spanText)>=0)) {
			if(theSpan.attr("disabled")) {
				theSpan.removeClass("OrderMemoSpanDisabled");
				theSpan.addClass("OrderMemoSpanEnabled");
				theSpan.attr("disabled",false);				
			}
		}
	}
}

function showCoupon(oldFrame) {
	if(isFirstOrder) return;
	var theFrame = oldFrame?oldFrame:"CreateOrder";
	pushFrame(theFrame,"SelectCoupon");
	$("#CouponList").html("");
	if(theFrame=="CreateOrder") {
		showSelectCoupon();
	} else {
		showValidCoupon();
	}
}

var selectedCouponId = 0;
var selectedCouponAmount = 0;
var discountAmount = 0;
var deliveryFee = 0;

function showSelectCoupon() {
	callServer("get_coupon_select_list", {time_type:dinnerNavId}, function(data){
		$("#CouponList").html(data.html);
	});
}

function showValidCoupon() {
	callServer("get_coupon_list", {validate:1}, function(data){
		$("#CouponList").html(data.html);
	});
}

function showInvalidCoupon() {
	callServer("get_coupon_list", {validate:0}, function(data){
		$("#CouponList").html(data.html);
	}, function(){});
}

function selectCoupon(coupon_id,amount,is_first_order_back) {
	if(amount == 11) {		
		if(product_fee>=20) {
			selectedCouponId = coupon_id;
			$(".CouponSelector").hide();
			$("#CouponSelector_"+coupon_id).show();
			window.history.back();
			$("#CouponValue").html("-￥"+amount);
			selectedCouponAmount = amount;
			updateOrderAmount();		
		} else {
			alert("该粮票是下单满20元才可用呢，快去加点菜吧！");
		}		
	} else {
		if(amount*2 <= product_fee || is_first_order_back != 1) {
			selectedCouponId = coupon_id;
			$(".CouponSelector").hide();
			$("#CouponSelector_"+coupon_id).show();
			window.history.back();
			$("#CouponValue").html("-￥"+amount);
			selectedCouponAmount = amount;
			updateOrderAmount();		
		} else {
			if (amount == 5) {
				alert("您选择的粮票是首单完成5元返券, 满10元可用");
			} else if (amount == 10) {
				alert("您选择的粮票是首单完成10元返券, 满20元可用");

			}
		}		
	}
}

function showComment() {
	callServer("get_comment_list",{shop_id:currentShopId},function(data){
		pushFrame("Shop","Comment");
		$("#CommentList").html(data.html);
	});
}

function loadOrderStatus(order_id) {
	callServer("get_order_status", {order_id:order_id}, function(data){
		pushFrame("Order","OrderStatus",function(){
			$("#CancelOrder").hide();
		});
		$("#OrderStatus").html(data.html);
	});
}

function showOrderStatus() {
	$("#OrderDetailList").hide();
	$("#OrderStatusList").show();
	$("#OrderTabStatus").addClass("Selected");
	$("#OrderTabDetail").removeClass("Selected");
}

function showOrderDetail() {
	$("#OrderStatusList").hide();
	$("#OrderDetailList").show();
	$("#OrderTabStatus").removeClass("Selected");
	$("#OrderTabDetail").addClass("Selected");
}

function showLogin() {
	pushFrame("Mine","Login");
	
}


function updateVCode() {
	vCodeCounter--;
	$("#LoginGetVCode").text(vCodeCounter+"s");
	if(vCodeCounter==0) {
		vCodeEnabled = true;
		$("#LoginGetVCode").addClass("Enabled");
		$("#LoginGetVCode").text("获取验证码");
	} else {
		setTimeout(updateVCode,1000);
	}
}

function getVCode() {
	if(!vCodeEnabled) return;
	vCodeEnabled = false;
	$("#LoginGetVCode").removeClass("Enabled");
	
	callServer("get_vcode", {phone:$("#LoginPhone").val()}, function(){
		$("#LoginGetVCode").text("60s");
		vCodeCounter = 60;
		setTimeout(updateVCode,1000);
	},function(errcode,errmsg){
		vCodeEnabled = true;
		$("#LoginGetVCode").addClass("Enabled");
		$("#LoginGetVCode").text("获取验证码");
		alert(errmsg);
	});
	
}

function login(_callback) {
	var params = {
		phone:$("#LoginPhone").val(),
		vcode:$("#LoginVCode").val()
	};
	
	callServer("login",params,function(data){
		$("#LoginDiv").hide();
		var html = "<img src='"+user_avatar+"'/>" +
			"<i>"+data.user_phone+"</i>";
		$("#LoginDivDone").html(html);
		$("#LoginDivDone").show();
		window.history.back();
		if(_callback) {
			_callback();
		}
	});
}

function checkLogin() {
	callServer("check_login",{},function(data){
		if(data.is_login) {
			$("#LoginDiv").hide();
			var html = "<img src='"+user_avatar+"'/>" +
				"<i>"+data.user_phone+"</i>";
			$("#LoginDivDone").html(html);
			$("#LoginDivDone").show();
		} else {
			$("#LoginDiv").show();
			$("#LoginDivDone").html("");
			$("#LoginDivDone").hide();
		}
	});
}

function saveAddressEditor(oldFrame) {
	var phone = $.trim($("#AddressEditorName").val());
	if(phone=="") {
		alert("联系姓名不能为空");
		return;
	}
	
	var phone = $.trim($("#AddressEditorPhone").val());
	if(phone=="") {
		alert("电话号码不能为空");
		return;
	}
	
	var address = $.trim($("#AddressEditorAddress").val());
	if(address=="") {
		alert("送餐地址不能为空");
		return;
	}
	
	var phone = $.trim($("#AddressEditorAddressRow2").val());
	if(phone=="") {
		alert("门牌号码不能为空");
		return;
	}
	
	if(addressEditorPosition!=null) {
		var params = $("#AddressEditorForm").serialize();
		params += "&lng="+addressEditorPosition.lng;
		params += "&lat="+addressEditorPosition.lat;
		callServer("save_address",params,function(){
			loadAddressChooserList(oldFrame);
			window.history.back();
			setTimeout(function(){
				if(mapAutocomplete) mapAutocomplete.hide();
			},500);
		});
	} else {
		//alert("正在定位地址，请稍候再试");
		var params = $("#AddressEditorForm").serialize();
		params += "&lng=116.3991766";
		params += "&lat=39.9125194";
		callServer("save_address",params,function(){
			loadAddressChooserList(oldFrame);
			window.history.back();
			setTimeout(function(){
				if(mapAutocomplete) mapAutocomplete.hide();
			},500);
		});
	}
}

function loadAddressChooserList(oldFrame) {
	//var theFrame = oldFrame?oldFrame:"CreateOrder";
	var theFrame = "CreateOrder";
	var address_id = $("#OrderAddressId").val();
	callServer("get_address_chooser_list",{frame:theFrame,address_id:address_id},function(data){
		$("#AddressChooserList").html(data.html);
	});
}

function createAddress (theFrame) {
	$("#AddressEditorForm")[0].reset();
	$("#AddressEditorId").val("");
	$("#AddressEditorOkButton").attr("onclick","saveAddressEditor('"+theFrame+"');")
	showAddressEditor();
}

function updateAddress(address_id,theFrame) {
	$("#AddressEditorForm")[0].reset();
	showAddressEditor();
	$("#AddressEditorOkButton").attr("onclick","saveAddressEditor('"+theFrame+"');")
	callServer("get_address", {address_id:address_id}, function(data){
		$("#AddressEditorName").val(data.contact_name);
		$("#AddressEditorPhone").val(data.phone);
		$("#AddressEditorAddress").val(data.user_address);
		$("#AddressEditorAddressRow2").val(data.user_address_row2);
		$("#AddressEditorId").val(data.address_id);
		
		//addressEditorPosition = null;
		var local = new BMap.LocalSearch(city, {
			onSearchComplete: function(){
				var pt = local.getResults().getPoi(0).point;
				addressEditorPosition = {lng:pt.lng,lat:pt.lat};
			}
		});
		local.search(data.user_address);
		
	});
}

function selectAddress(address_id) {
	callServer("get_address_html", {address_id:address_id}, function(data){
		$("#OrderAddress").html(data.html);
		$("#OrderAddress").show();
		$("#NoOrderAddress").hide();
		$(".AddressChooserTick").attr("src","images/yes_grey.png");
		$("#AddressChooserTick_"+address_id).attr("src","images/yes.png");
		window.history.back();
	});
}

var created_order_id = 0;

function wx_pay_order(order_id, coupon_id) {
	callServer("pay_order",{"order_id":order_id},function(data){
		var wx_params = {
			"appId":data['appId'],
			"timeStamp":data['timeStamp'],
			"nonceStr":data['nonceStr'],
			"package":data['package'],
			"paySign":data['paySign'],
			"signType":"MD5"
		};
		
		WeixinJSBridge.invoke('getBrandWCPayRequest',wx_params,
			function(res){
				if(res.err_msg == "get_brand_wcpay_request:ok" ) {
					clearBackStack();
					$("#CreateOrder").hide();
					showOrder();
				} else {
					resetCoupon(coupon_id);
					alert("您取消了支付或支付失败");
				}
			}
		);
	});
}

function wx_pay_order_activity_1024(order_id) {
	callServer("pay_order",{"order_id":order_id},function(data){
		var wx_params = {
			"appId":data['appId'],
			"timeStamp":data['timeStamp'],
			"nonceStr":data['nonceStr'],
			"package":data['package'],
			"paySign":data['paySign'],
			"signType":"MD5"
		};
		
		WeixinJSBridge.invoke('getBrandWCPayRequest',wx_params,
			function(res){
				if(res.err_msg == "get_brand_wcpay_request:ok" ) {
					clearBackStack();
					//window.history.back();

					$("#activity_1024").hide();
					$("#pay_success").show();
				} else {
					alert("您取消了支付或支付失败");
				}
			}
		);
	});
}

function createAndPayOrder() {
	var address_id = $("#OrderAddressId").val();
	if( (!address_id) || address_id=="") {
		alert("请选择地址");
		return;
	}
	
	
	//if(created_order_id) {
	//	wx_pay_order(created_order_id);
	//	return;
	//}
	
	var params = {
		address_id:address_id,
		delivery_time_index:$("#OrderTimeSelector").val(),
		order_memo:$("#OrderMemoLabel").html(),
		delivery_method:isTakeawayValue?2:1,
		cart_items:JSON.stringify(cartItems),
		shop_id:currentShopId,
		time_type:dinnerNavId,
		total_fee:total_fee,
		coupon_id:selectedCouponId
	};
	
	callServer("create_order",params,function(data){
		created_order_id = data.order_id;
		
		if(total_fee>0) {
			wx_pay_order(created_order_id, selectedCouponId);
		} else {
			clearBackStack();
			$("#CreateOrder").hide();
			showOrder();
		}
		
	});
}

function showFeedback() {
	pushFrame("Mine", "Feedback",function(){
		$("#FeedbackAdvice").blur();
		$("#FeedbackPhone").blur();
	});
	$("#FeedbackAdvice").width($(window).width()-40);
	$("#FeedbackPhone").width($(window).width()-40);
	$("#FeedbackAdvice").val("");
	$("#FeedbackPhone").val("");
}

function saveFeedback() {
	var params = {
		phone:$("#FeedbackPhone").val(),
		content:$("#FeedbackAdvice").val()
	}
	callServer("save_feedback", params, function(){
		window.history.back();
	});
	
}

function resetCoupon(coupon_id) {
	callServer("reset_coupon",{coupon_id:coupon_id},function(data){
		window.history.back();
		$("#CouponValue").html("");
	});
}

function wxlogin() {
	callServer("wx_login", {}, function(data){
		user_avatar = data.user_avatar;
	});
}

var editCommentOrderId = null;
var editCommentStar = 5;

function editComment(order_id) {
	editCommentOrderId = order_id;
	pushFrame('OrderStatus','EditComment');
	$("#EditCommentText").width($("body").width()-42);
	editCommentStarClick(5);
	$("#EditCommentText").val("");
}

function editCommentStarClick(index) {
	editCommentStar = index;
	for(var i=1;i<=5;i++) {
		$("#EC_Star_"+i).attr('src',i<=index?"images/star_on_big.png":"images/star_off_big.png");
	}
}

function saveComment() {
	var comment_detail = $("#EditCommentText").val();
	callServer("save_order_comment", 
		{"order_id":editCommentOrderId,"comment_mark":editCommentStar,"comment_detail":comment_detail},
		function(){
			window.history.back();
			loadOrderStatus(editCommentOrderId);
		}
	);
}

function showShopMap(shop_id) {
	$("#ShopMapContainer").width($(window).width());
	$("#ShopMapContainer").height($(window).height()-60);
	callServer("get_shop_info",{shop_id:shop_id},function(data){
		pushFrame("Shop", "ShopMap");
		var map = new BMap.Map("ShopMapContainer");
		var point = new BMap.Point(data.shop_lng, data.shop_lat);
		map.centerAndZoom(point, 15);
		map.setCurrentCity(city);
		map.enableScrollWheelZoom(true);
		var marker = new BMap.Marker(point);
		map.addOverlay(marker);
		var html = "<span>"+data.shop_name+"</span><i>"+data.shop_address+data.shop_address_row2+"</i>";
		$("#ShopMapText").html(html);
	});
}

function showCurrentPositionMap() {
	$("#CurrentPositionMapContainer").width($(window).width());
	$("#CurrentPositionMapContainer").height($(window).height()/2);

	var map = new BMap.Map("CurrentPositionMapContainer");
	var point = new BMap.Point(currentPosition.lng, currentPosition.lat);
	map.centerAndZoom(point, 15);
	map.setCurrentCity(city);
	map.enableScrollWheelZoom(true);
	var marker = new BMap.Marker(point);
	map.addOverlay(marker);

	var options = {
			onSearchComplete: function(results){
				// 判断状态是否正确
				if (local.getStatus() == BMAP_STATUS_SUCCESS){
					var s = [];
					for (var i = 0; i < results.getCurrentNumPois(); i ++){
						s.push(results.getPoi(i).title + ", " + results.getPoi(i).address);
					}
					document.getElementById("r-result").innerHTML = s.join("<br/>");
				}
			}
	};
	
	var local = new BMap.LocalSearch(map, options);

	local.search(currentAddress);
}

var timeOrderId = 0;
var timeOrderShopPhone = 0;

function cancelOrder(order_id,time_out,shop_phone) {
	timeOrderId = order_id;
	timeOrderShopPhone = shop_phone;
	if(time_out) {
		$("#CancelOrder").show();
	} else {
		callServer("cancel_order", {order_id:order_id}, function(){
			window.history.back();
			showOrder();
		});
	}
}

function TimeoutOrderWait() {
	$("#CancelOrder").hide();
}

function TimeoutOrderCancel() {
	callServer("cancel_order", {order_id:timeOrderId}, function(){
		$("#CancelOrder").hide();
		window.history.back();
		showOrder();
	});
}

function TimeoutOrderCall() {
	window.location.href="tel:"+timeOrderShopPhone;
	$("#CancelOrder").hide();
}

function searchAndSelectAddress() {
	pushFrame("AddressEditor","BMapAutoAddress",function(){
		//alert("back");
	});

	initAddressAutocomplete();
	
	//showCurrentPositionMap();
}

function selectAutocompleteAddressFromSearch(title,address,longitude,latitude) {
	$("#AddressText").text(address);
	currentPosition = {lng:longitude,lat:latitude};
	clickDinnerNav(dinnerNavId);
	$("#AddressInput").hide();
	addAddressHistory({title:title,address:address});
	window.history.back();
}

function loginPhoneCheck() {
	var text = $("#LoginPhone").val();
	if(text!="") {
		$("#LoginGetVCode").addClass("Enabled");
	} else {
		$("#LoginGetVCode").removeClass("Enabled");
	}
}

function loginVCodeCheck() {
	var text = $("#LoginVCode").val();
	var text1 = $("#LoginPhone").val();
	if(text!="" && text1!="") {
		$("#LoginOkButton").removeClass("Disabled");
	} else {
		$("#LoginOkButton").removeClass("Disabled");
	}
}

function buyTicket() {
	callServer("check_login",{},function(data){
		if(data.is_login) {
			createAndPayOrder_Activity_1024();
		} else {
			pushFrame("activity_1024","Login");
		}
	});

	//pushFrame("activity_1024","pay_success");	
}

function createAndPayOrder_Activity_1024() {
	var address_id = 357;
	total_fee = 1.00;
	
	var params = {
		address_id:address_id,
		delivery_time_index:22,
		order_memo:$("#OrderMemoLabel").html(),
		delivery_method:1,
		cart_items:'[{"product_id":564,"count":1}]',
		shop_id:55,
		time_type:0,
		total_fee:1.00,
		coupon_id:0
	};
	
	callServer("create_order_activity_1024",params,function(data){
		created_order_id = data.order_id;
		
		if(total_fee>0) {
			wx_pay_order_activity_1024(created_order_id);
		}
		
	});
}
