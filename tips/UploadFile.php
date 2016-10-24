public void UploadFile()
{
    if (HttpContext.Current.Request.Files.AllKeys.Any())
    {
        var httpPostedFile = HttpContext.Current.Request.Files["file"];
  bool folderExists = Directory.Exists(HttpContext.Current.Server.MapPath("../uploads/"));
        if (!folderExists)
            Directory.CreateDirectory(HttpContext.Current.Server.MapPath("../uploads/"));
        var fileSavePath = Path.Combine(HttpContext.Current.Server.MapPath("../uploads/"),
                                        httpPostedFile.FileName);
        httpPostedFile.SaveAs(fileSavePath);

        if (File.Exists(fileSavePath))
        {
            //AppConfig is static class used as accessor for SFTP configurations from web.config
            using (SftpClient sftpClient = new SftpClient(AppConfig.SftpServerIp,
                                                         Convert.ToInt32(AppConfig.SftpServerPort),
                                                         AppConfig.SftpServerUserName,
                                                         AppConfig.SftpServerPassword))
            {
                sftpClient.Connect();
                if (!sftpClient.Exists(AppConfig.SftpPath + "UserID"))
                {
                    sftpClient.CreateDirectory(AppConfig.SftpPath + "UserID");
                }

                Stream fin = File.OpenRead(fileSavePath);
                sftpClient.UploadFile(fin, AppConfig.SftpPath + "/" + httpPostedFile.FileName,
                                      true);
                fin.Close();
                sftpClient.Disconnect();
            }
        }
    }
}