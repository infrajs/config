import conf from '/-config/'
let Config = {}
window.Config = Config
Config.conf = conf
Config.get = name => {
    if (!name) return Config.conf;
    return Config.conf[name];
}

export { Config }