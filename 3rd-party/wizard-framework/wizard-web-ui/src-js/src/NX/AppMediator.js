import UILoader from "./UILoader";
import Node from "../UX/Node";

class AppMediator {
  init(rootDom) {
    this.rootDom = rootDom;
    this.activated = true;
    this._callbacks = [];
    this._nodes = {};

    const uuid = sessionStorage.getItem('AppMediator.uuid');

    if (!uuid) {
      this.uuid = Math.random().toString(36).substring(7);
      sessionStorage.setItem('AppMediator.uuid', this.uuid);
    } else {
      this.uuid = uuid;
    }
  }

  /**
   * @param contextUrl
   * @param wsUrl
   * @param sessionId
   */
  startWatching(contextUrl, wsUrl, sessionId) {
    this.contextUrl = contextUrl;
    this.wsUrl = wsUrl;
    this.sessionId = sessionId;

    const loc = window.location;
    let newUri = '';

    if (loc.protocol === "https:") {
      newUri = "wss:";
    } else {
      newUri = "ws:";
    }

    newUri += "//" + loc.host;
    newUri += wsUrl;

    this.ws = new WebSocket(newUri);

    this.ws.onerror = () => {
      if (sessionStorage.getItem('AppMediator.reloading')) {
        setTimeout(() => window.location.reload(true), 200);
      }
    };

    this.ws.onopen = () => {
      sessionStorage.setItem('AppMediator.reloading', false);

      this.send('initialize', {});
      this.sendIfCan('ui-ready', {
        location: {
          contextUrl: contextUrl,
          hash: window.location.hash ? window.location.hash.substr(1) : '',
          path: window.location.pathname,
          host: window.location.host,
          port: window.location.port,
          protocol: window.location.protocol,
          target: window.location.target,
        }
      });
    };

    this.ws.onclose = () => {
      this.ws = null;

      if (this.node) {
        this.node.free();
      }
    };

    const handlers = {
      'ui-render': this.triggerRenderView,
      'ui-reload': this.triggerReload,
      'ui-alert' : this.triggerAlert,
      'ui-set-property': this.triggerSetProperty,
      'ui-call-method': this.triggerCallMethod,
      'ui-event-link': this.triggerOnEventLink,
      'ui-create-node': this.triggerCreateNode,
    };

    this.ws.onmessage = (event) => {
      const message = JSON.parse(event.data);
      const type = message.type;

      console.debug("AppMediator.receive", message);

      if (handlers.hasOwnProperty(type)) {
        handlers[type].call(this, message);
      } else {
        switch (type) {
          case "system-console-log":
            const text = message['message'];

            switch (message['kind']) {
              case 'warn':
                console.warn(text);
                break;
              case 'error':
                console.error(text);
                break;
              case 'info':
                console.info(text);
                break;
              case 'debug':
                console.debug(text);
                break;
              case 'trace':
                console.trace(text);
                break;
              case 'clear':
                console.clear();
                break;
              default:
                console.log(text);
                break;
            }

            break;

          case "system-eval":
            const {script, callback} = message;
            const result = eval(script);

            if (callback) {
              callback(result);
            }

            break;

          case "system-callback":
            const { id } = message;

            if (this._callbacks[id]) {
              this._callbacks[id].apply(message);
              delete this._callbacks[id];
            }

            break;

          case "history-push":
            const url = message['url'];
            const title = message['title'];
            const hash = message['hash'];

            if (window.location.pathname === url) {
              if (title !== undefined) {
                document.title = title;
              }
              if (hash !== undefined) {
                window.location.hash = hash;
              }

              break;
            }

            window.history.pushState(null, message['title'], url);

            if (title !== undefined) {
              document.title = title;
            }
            if (hash !== undefined) {
              window.location.hash = hash;
            }

            break;

          case "page-set-properties":
            if (message['title'] !== undefined) {
              document.title = message['title'];
            }

            if (message['hash'] !== undefined) {
              window.location.hash = message['hash'];
            }

            break;
        }
      }
    };

    setInterval(() => {
      const docVisible = !document.hidden;

      if (docVisible !== this.activated) {
        this.activated = docVisible;

        if (this.activated) {
          this.sendIfCan('activate', {});
        }
      }
    }, 1);
  }

  parseValue(value) {
    if (value instanceof Object) {
      if (value.hasOwnProperty('$node')) {
        return this.findNodeByUuidGlobally(value['$node']);
      } else if (value.hasOwnProperty('$createNode')) {
        const schema = value['$createNode'];

        const uiLoader = new UILoader();
        return uiLoader.load(schema);
      } else if (value.hasOwnProperty('$callable')) {
        const uuid = value['$callable'];

        return () => {
          this.sendIfCan('callback-trigger', {
            'uuid': uuid,
            'args': arguments
          });
        };
      }
    }

    if (value instanceof Array) {
      for (let i = 0; i < value.length; i++) {
        value[i] = this.parseValue(value[i]);
      }

      return value;
    }

    if (value instanceof Object) {
      const newValue = {};

      for (let key in value) {
        if (value.hasOwnProperty(key)) {
          newValue[key] = this.parseValue(value[key]);
        }
      }

      return newValue;
    }

    return value;
  }

  prepareValue(value) {
    if (value instanceof Node) {
      if (value.uuid !== undefined) {
        return {'$node': value.uuid}
      } else {
        console.error('Cannot send unregistered node', value);
        return null;
      }
    }

    if (value instanceof Array) {
      for (let i = 0; i < value.length; i++) {
        value[i] = this.prepareValue(value[i]);
      }

      return value;
    }

    if (value instanceof Object) {
      const newValue = {};

      for (let key in value) {
        if (value.hasOwnProperty(key)) {
          newValue[key] = this.prepareValue(value[key]);
        }
      }

      return newValue;
    }

    return value;
  }

  /**
   * @param node
   * @param data
   * @param callback
   */
  sendUserInput(node, data, callback = null) {
    if (!this.ws) {
      return;
    }

    if (!document.hidden) {
      setTimeout(() => {
        let newData = data;

        if (typeof data === "function") {
          newData = data();
        }

        this.sendIfCan('ui-user-input', {
          'uuid': node.uuid,
          'data': this.prepareValue(newData)
        }, callback)
      }, 0);
    } else {
      console.warn('Ignore User input');
    }
  }

  /**
   * @param type
   * @param message
   * @param callback
   * @returns {boolean}
   */
  sendIfCan(type, message, callback) {
    if (this.ws !== undefined) {
      this.send(type, message, callback);
      return true;
    } else {
      return false;
    }
  }

  /**
   * @param type
   * @param message
   * @param callback
   */
  send(type, message, callback) {
    if (this.ws === undefined) {
      throw "Mediator is not in watching state.";
    }

    message.type = type;
    message.id = Math.random().toString(36).substring(7);
    message.sessionId = this.sessionId;
    message.sessionIdUuid = this.uuid;

    if (callback) {
      this._callbacks[message.id] = callback;
      message.needCallback = true;
    }

    console.debug("AppMediator.send", message);

    this.ws.send(JSON.stringify(message));
  }

  findNodeByUuidGlobally(uuid) {
    let found = this.findNodeByUuid(uuid, this.node);

    if (found === null) {
      if (this._nodes.hasOwnProperty(uuid)) {
        found = this._nodes[uuid];
      }
    }

    if (found === null) {
      for (const key in this._nodes) {
        if (this._nodes.hasOwnProperty(key)) {
          const found = this.findNodeByUuid(uuid, this._nodes[key]);

          if (found !== null) {
            return found;
          }
        }
      }
    }

    return found;
  }

  findNodeByUuid(uuid, node) {
    if (uuid === node.uuid) {
      return node;
    }

    let children = node.innerNodes();

    for (let i = 0; i < children.length; i++) {
      if (children[i].uuid === uuid) {
        return children[i];
      }

      const found = this.findNodeByUuid(uuid, children[i]);

      if (found !== null) {
        return found;
      }
    }

    return null;
  }

  triggerEvent(node, event, e) {
    const data = {
      type: e.type,
      which: e.which,
      result: e.result,
      namespace: e.namespace,
      position: [e.pageX, e.pageY]
    };

    this.sendIfCan('ui-trigger', {
      uuid: node.uuid,
      event: event,
      data: this.prepareValue(data),
    });
  }

  /**
   * Render new view.
   * @param message
   */
  triggerRenderView(message) {
    const uiLoader = new UILoader();
    this.node = uiLoader.load(message['schema'], this);

    this.rootDom.empty();
    this.rootDom.append(this.node.dom);
  }

  triggerAlert(message) {
    const text = message['text'];
    alert(text);
  }

  triggerCallMethod(message) {
    const uuid = message['uuid'];
    const method = message['method'];
    const args = this.parseValue(message['args'] || []);

    const node = this.findNodeByUuidGlobally(uuid);

    if (node !== null) {
      node[method].apply(node, args);
    } else {
      console.warn(`Failed to set property, node with uuid = ${uuid} is not found`);
    }
  }

  triggerSetProperty(message) {
    const uuid = message['uuid'];
    const property = message['property'];
    const value = this.parseValue(message['value']);

    const node = this.findNodeByUuidGlobally(uuid);

    if (node !== null) {
      node[property] = value;
    } else {
      console.warn(`Failed to set property, node with uuid = ${uuid} is not found`);
    }
  }

  triggerOnEventLink(message) {
    const uuid = message['uuid'];
    const event = message['event'];

    const node = this.findNodeByUuidGlobally(uuid);

    if (node !== null) {
      node.off(`${event}.AppMediator`);

      node.on(`${event}.AppMediator`, (e) => {
        this.triggerEvent(node, event, e);
      });
    } else {
      console.warn(`Failed to link event ${event}, node with uuid = ${uuid} is not found`);
    }
  }

  triggerCreateNode(message) {
    const schema = message['schema'];

    const uiLoader = new UILoader();
    const node = uiLoader.load(schema, this);

    this._nodes[node.uuid] = node;
  }

  triggerReload(message) {
    sessionStorage.setItem('AppMediator.reloading', true);
    setTimeout(() => window.location.reload(true), 50);
  }
}

export default new AppMediator();