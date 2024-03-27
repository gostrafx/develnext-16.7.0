import Node from './Node';
import Utils from './util/Utils';

class ProgressBar extends Node {
  get progress() {
    return Utils.toPt(this.dom.find('> .progress-bar').css('width'));
  }

  set progress(value) {
    this.dom.find('> .progress-bar').css('width', value + '%');
  }

  get kind() {
    const dom = this.dom.find('> .progress-bar');

    if (dom.hasClass('progress-bar-success')) {
      return 'success';
    } else if (dom.hasClass('progress-bar-info')) {
      return 'info';
    } else if (dom.hasClass('progress-bar-warning')) {
      return 'warning';
    } else if (dom.hasClass('progress-bar-danger')) {
      return 'danger';
    }

    return 'default';
  }

  set kind(value) {
    const dom = this.dom.find('> .progress-bar');
    
    dom.removeClass(`progress-bar-${this.kind}`);
    dom.addClass(`progress-bar-${value}`);
  }

  get animated() {
    const dom = this.dom.find('> .progress-bar');
    return dom.hasClass('active');
  }

  set animated(value) {
    const dom = this.dom.find('> .progress-bar');

    if (value) {
      dom.addClass('active');
    } else {
      dom.removeClass('active');
    }
  }

  get striped() {
    const dom = this.dom.find('> .progress-bar');
    return dom.hasClass('progress-bar-striped');
  }

  set striped(value) {
    const dom = this.dom.find('> .progress-bar');

    if (value) {
      dom.addClass('progress-bar-striped');
    } else {
      dom.removeClass('progress-bar-striped');
    }
  }

  get value() {
    let width = this.dom.find('> .progress-bar').css('width');

    if (!width) {
      return 0;
    }

    return parseInt(width);
  }

  set value(v) {
    this.dom.find('> .progress-bar').css('width', v + "%");
  }

  createDom() {
    const dom = jQuery('<div class="progress ux-progress-bar"><div class="progress-bar" role="progressbar"></div></div>');

    return dom;
  }
}

export default ProgressBar;
