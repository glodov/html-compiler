import $ from 'jquery';
import { PopupLink } from './modules/popup';
import { breakpoints } from 'styled-bootstrap-responsive-breakpoints';

class Application
{
	constructor() {
		$(window).on('resize load', () => {
			this.fullscreen();
		});

		this.anchors();

		this.popups();
	}

	fullscreen() {
		$('[fullscreen]').each(function () {
			let $element = $(this);

			let point = $element.attr('fullscreen');
			if (undefined === breakpoints[point]) {
				point = 'xl';
			}
			let breakpoint = parseInt(breakpoints[point]);

			if ($(window).width() <= breakpoint) {
				let height = $(window).height();
				// debugger;
				height -= $('body > header:first').height();
				$element.outerHeight(height);
			} else {
				$element.css('height', 'auto');
			}
		});
	}

	anchors() {
		if (location.hash && $(window).scrollTop() > 0) {
			setTimeout(() => {
				window.scrollTo(0, 0);
			}, 1);
		}

		$(() => {
			if (location.hash) {
				this.scrollTo(location.hash);
			}
		});

		$('body').delegate('a[href^="#"]', 'click', (event) => {
			event.preventDefault();
			this.scrollTo($(event.target).attr('href'))
		});
	}

	scrollTo(href) {
		let $target = $(href);
		if ($target.length) {
			$('html, body').animate({
				scrollTop: $target.offset().top
			}, 1000, () => {
				location.hash = href.substring(1);
				$target.focus();
				if (!$target.is(':focus')) {
					$target.attr('tabindex', '-1');
					$target.focus();
				}
			});
		}
	}

	popups() {
		let popup = new PopupLink('modal-lg');
		popup.assign('a[popup-link]', 'href')
	}
}


let app = new Application();

export default Application;