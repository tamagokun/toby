var analytics=analytics||[];analytics.load=function(e){var t=document.createElement("script");t.type="text/javascript",t.async=!0,t.src=("https:"===document.location.protocol?"https://":"http://")+"d2dq2ahtl5zl1z.cloudfront.net/analytics.js/v1/"+e+"/analytics.min.js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(t,n);var r=function(e){return function(){analytics.push([e].concat(Array.prototype.slice.call(arguments,0)))}},i=["identify","track","trackLink","trackForm","trackClick","trackSubmit","pageview","ab","alias","ready"];for(var s=0;s<i.length;s++)analytics[i[s]]=r(i[s])};
analytics.load("5g9bdq58dz");

jQuery(document).ready(function($) {
	//set up marked
	marked.setOptions({
		gfm : true,
		tables: true
	});

	//set up nav spying
	var spy_nav = function() {
		var active_nav_item = false;
		$("#nav li a").each(function() {
			var el = $('a.spy[href="'+$(this).attr('href')+'"]');
			var active = $(window).scrollTop() <= el.offset().top - 10 ? false : true;
			if(active) active_nav_item = this;
			$(this).attr('class', '');
		});
		if(active_nav_item) $(active_nav_item).attr('class','active');
	};
	$(window).on('scroll', spy_nav);

	$.ajax('https://api.github.com/repos/tamagokun/toby/contents/docs/docs.md', {
		dataType: "jsonp",
		success: function(data) {
			if(typeof data.data.content == "undefined") return;
			var content = data.data.content;
			if(data.data.encoding == "base64")
				content = window.atob(content.replace(/\n/g,""));
			var lexed = marked.lexer(content);
			var parsed = marked.parser(lexed);
			//TODO: Loader?
			$("#main").append(parsed);
			$("code[class*='lang']").each(function() {
				$(this).attr('data-language',$(this).attr('class').split('-').pop());
			});
			$("#main h2").each(function() {
				anchor = this.innerHTML.toLowerCase().replace(/[^a-z0-9:_.-]/g, '-').replace(/-+/g, '-').replace(/^-+|-+$/g, '');
				$(this).wrap('<a name="'+anchor+'" href="#'+anchor+'" class="spy"></a>');
				$("#nav ul").append('<li><a href="#'+anchor+'">'+this.innerHTML+'</a></li>');
			});
			Rainbow.color();
		}
	});
});
