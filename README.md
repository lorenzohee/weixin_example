### 微信公众开发实例实现功能：

1. access_token的获取
2. 获取消息类型并区分
3. 图片、音频、视频的下载
4. 多客服服务
5. 和mysql数据库的链接（视频和图片文件下载后在数据库中进行记录）

### 配置步骤：

1. 修改wx_main.php里面的token
2. 将库中的两个文件放在你的服务器中（保证安装了php和mysql的环境）
3. 在微信公众平台中设置服务器配置（url地址填写为：http://xxx.xxx.xxx.xxx/wx_main.php, token设置为和wx_main.php中设置的一样）
4. 配置完后，平台将会提示你配置成功，若不成功请查看token的设置
5. 接下来你就可以根据wx_main.php进行定制化开发了！
