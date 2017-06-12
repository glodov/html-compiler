import $ from 'jquery';

const template = `
	<div class="modal fade" tabindex="-1" role="dialog">
	  <div class="modal-dialog modal-lg" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title">Modal title</h5>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>
	      <div class="modal-body p-3 p-md-4 px-lg-5">
	        ...
	      </div>
	    </div>
	  </div>
	</div>
	`;

class PopupLink
{
	constructor(className = '') {
		this.className = className;
	}

	assign(selector = 'a[popup-link]', urlAttr = 'href') {
		$('body').delegate(selector, 'click', (event) => {
			event.preventDefault();
			let $a = $(event.target);
			$.get($a.attr(urlAttr), (data) => {
				let $div = $('<div />');
				$div.html(data);
				let title = $(data).filter('title').text();
				$div = $div.find('[popup-content]:first');
				if ($div.length) {
					let $modal = $(template);
					$modal.find('.modal-body').html('').append($div);
					$modal.find('.modal-title').text(title);
					$modal.modal('show');
				}
			});
		});
	}
}

export {
	PopupLink
};